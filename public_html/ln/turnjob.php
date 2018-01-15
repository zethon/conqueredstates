<?
//-----------------------------------------------------------------------------
// $RCSfile: turnjob.php,v $ $Revision: 1.8 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./nations.php');
require_once('./resources.php');
require_once('./issues.php');

$query_count = 0;

header("Content-Type: text/plain");
?>

Turnjob for Server: <? echo $gGameServerName; ?>

Date/Time: <? echo date('d-m-Y H:i:s'); ?>

<?

$nationLoader = new NationLoader();
$nationWriter = new NationWriter();
$regionLoader = new RegionLoader();
$regionWriter = new RegionWriter();
$resourceConCalc = new ResConsumptionCalc();
$issueLoader = new IssueLoader();
$modtokenizer = new TextModTokenizer();

// create the resource name array
$resArray = array();
$datatable = $DBObj->query("SELECT * FROM resources");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$resobj = new Resource();
	$resobj->resource_id = $storeitem['resource_id'];
	$resobj->name = $storeitem['resource_name'];
	$resobj->unit = $storeitem['resource_unit'];
	$resobj->producername = $storeitem['producer_name'];
	$resArray[$storeitem['resource_id']] = $resobj;
}

// all the nations that have a region 
$datastore = $DBObj->query("SELECT DISTINCT nation_id FROM nation_regions ORDER BY nation_id");
while ($storeitem = $DBObj->fetch_array($datastore))
{
	$start = microtime_float();
	$nation = $nationLoader->loadNationWithStats($storeitem[nation_id]);

	if ($nation == null || $nation->id == 0)
	{
		$stop = microtime_float()-$start;
		print ("Error Loading Nation ID: $storeitem[nation_id]\n");
		continue;
	}
	
	print ("***** Nation: $nation->name ($nation->id)*****\n");
	// using new stats, calculate stats
	$nation->CalcEconomicStats();

	// calculate new populations by region
	$oldnationpop = $nation->GetPopulation();
	$growthrate = $nation->GetGrowthRate();
	$idx = 0;
	foreach ($nation->regions as $region)
	{
		print ("\tRegion: $region->name ($region->id)\n");
		print ("\t\tOld Population: $region->population\n");
		
		// calculate the new population and the net change in people
		$newregpop = ceil($region->population + (($region->population * $growthrate)/12));
		$change = $newregpop - $region->population;
		
		// update the region and nation objects
		$region->setProp('population',$newregpop);
		$nation->regions[$idx] = $region;
		
		$regionWriter->saveRegion($region);
		print ("\t\tNew Population: $region->population\n");
		print ("\t\tNet Population Change: $change\n");
		
	}
	
	//$nation = $nationLoader->loadNationWithStats($storeitem[nation_id]);
	print ("--- Old Total National Pop: $oldnationpop\n");
	print ("--- New Total National Pop: ".$nation->GetPopulation()."\n");
	
	//TODO: tweak the economic_strength and health a tad based on the population change
	

	// recalc the economic stats
	$nation->CalcEconomicStats();
	
	// calculate the day's tax intake
	$govintake = sprintf('%.0f',$nation->ecstats->govtbudg/365);
	print ("\n--- Gov't Intake: $govintake\n");
	$old = $nation->balance;
	//print ("--- Old Gov't Balance: $nation->balance\n");
	$nation->setProp('balance',sprintf('%.0f',$nation->balance + $govintake));
	//print ("--- Change in Gov't Balance: ".(number_format(($nation->balance - $old)))."\n");
	print ("--- New Gov't Balance Before Expenses: $nation->balance\n");	
	
	// expenses
	foreach ($nation->expense_names as $name)
	{
		if ($nation->expenses->$name > 0)
		{
			$temp = sprintf('%.0f',(($nation->ecstats->govtbudg/365) * $nation->expenses->$name));
			print ("--- $name Daily Allowance: $temp\n");
			$nation->setProp('balance',sprintf('%.0f',$nation->balance - $temp));
		} // TODO: generate issues/news
	}
	print ("--- Gov't Balance After Expenses: $nation->balance\n");	
	//$nationWriter->saveNation(array('balance',$nation->balance),$nation);
	
	// process resources
	print ("\n* BEGIN RESOURCE PROCESS\n");
	
	// loop through each resource
	foreach ($resArray as $resobj)
	//foreach ($nation->resources as $nresource)
	{
		print ("\tResource: $resobj->name ($resobj->resource_id)\n");
		
		$nresource = $nation->resources[$resobj->resource_id];
		if ($nresource == null)
			continue;
		
		$producercost = 0;
		$totalprod = 0;
		$regionstr = "";
		
		
		$oldval = $nresource->quantity;
	
		foreach ($nation->regions as $region)
		{
			$rresource = $region->resources[$nresource->resource_id];
			
			if ($rresource == null)
				continue;
			
			if (strlen($regionstr) > 0)
				$regionstr .= ";";
				
			if ($rresource->producercount > $rresource->producerlimit)
				$rresource->setProp('producercount',$rresource->producerlimit);

			$totalproduced = ($rresource->productionrate * $rresource->producercount);
			
			// check that there are enough resources left to extract, if not extracted what can be extracted
			if (($rresource->resourceextracted + $totalproduced) > $rresource->resourcecapacity && $rresource->resourcecapacity != -1)
				$totalproduced = $rresource->resourcecapacity - $rresource->resourceextracted;
				
			// save the region resource object
			$rresource->setProp('resourceextracted',$rresource->resourceextracted + $totalproduced);
			$rresource->Save();
				
			//update some helper variables
			if ($totalproduced > 0)
				$producercost += ($rresource->producercount * $rresource->producercost);
				
			$totalprod += $totalproduced;
			$regionstr .= $region->name." ($region->id)";

			// update the nation quantity object			
			$nresource->setProp('quantity',$nresource->quantity + $totalproduced);
		}

		// figure out the consumption of the resource
		$resourceConCalc->setProp('nation',$nation);
		$quantityconsumed = ceil($resourceConCalc->CalculateConsumption($nresource->resource_id)/365);
		$nresource->setProp('quantity',$nresource->quantity - $quantityconsumed);
		
		// TODO: calculate and update the extracted infor

		// print the info
		print ("\t\tRegions: $regionstr\n");
		print ("\t\tOld # $nresource->unit"."s onhand: $oldval\n");
		print ("\t\t# of $nresource->unit"."s consumed: $quantityconsumed\n");
		print ("\t\t# of $nresource->unit"."s produced: $totalprod\n");		
		print ("\t\tNew # $nresource->unit"."s onhand: $nresource->quantity\n");
		print ("\t\tNet Change: ".($nresource->quantity - $oldval)."\n");
		print ("\t\tProducer Cost: $producercost\n");
		
		$nresource->Save($nation);
		$nation->setProp('balance',sprintf('%.0f',$nation->balance - $producercost));		
		
		// write the nation back to the DB
		$nation->stats->Save();
		$nation->expenses->Save();
		$nationWriter->saveNation(array('balance',$nation->balance),$nation);
	}
	
	print ("--- New Gov't Balance After Resource Expenses: $nation->balance\n");	
	print ("* END RESOURCE PROCESS\n");
	
	$stop = microtime_float()-$start;
	print ("--- Nation Processing time: $stop seconds\n");
	print ("***** END NATION: $nation->name *****\n\n");
	print ("<hr>\n");
	// update the region index counter
	$idx++;
}

print "<BR><H1>[$query_count]</h1>";

?>

