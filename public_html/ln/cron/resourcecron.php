<?php 
//-----------------------------------------------------------------------------
// $RCSfile: resourcecron.php,v $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/15 20:32:57 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('./../');
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./nations.php');
require_once('./resources.php');
require_once('./issues.php');

define('CVS_REVISION', '$RCSfile: resourcecron.php,v $ - $Revision: 1.2 $');
if (defined('CVS_REVISION'))
{
	$re = '#^\$' . 'RCS' . 'file: (.*\.php),v ' . '\$ - \$' . 'Revision: ([0-9\.]+) \$$#siU';
	$cvsversion = preg_replace($re, '\1, CVS v\2', CVS_REVISION);
}

// print the header and report info
header("Content-Type: text/plain");
print ("File  : $cvsversion\n");
print ("Server: $gGameServerName\n");
print ("Date  : ".(date('d-m-Y H:i:s'))."\n");
print ("--------------------------------------------\n\n");
$start = microtime_float(); // start the timer

// create helper objects
$nationLoader = new NationLoader();
$nationWriter = new NationWriter();
$regionLoader = new RegionLoader();
$regionWriter = new RegionWriter();
$resourceConCalc = new ResConsumptionCalc();

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
	$nation = $nationLoader->loadNationWithStats($storeitem[nation_id]);
	
	if ($nation == null || $nation->id == 0)
	{
		print ("Error Loading Nation ID: $storeitem[nation_id]\n");
		continue;
	}	
	
	print ("*************** Nation: $nation->name ($nation->id) ***************\n");
	$nation->CalcEconomicStats();
	$old = $nation->balance;
	
	// loop through each resource
	foreach ($resArray as $resobj)
	{
		print ("Resource: $resobj->name ($resobj->resource_id)\n");
		
		$nresource = $nation->resources[$resobj->resource_id];
		if ($nresource == null)
		{
			print ("$resobj->name is not defined for this nation\n");
			continue;
		}
		
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
		print ("\tRegions: $regionstr\n");
		print ("\tOld # $nresource->unit"."s onhand: $oldval\n");
		print ("\t# of $nresource->unit"."s consumed: $quantityconsumed\n");
		print ("\t# of $nresource->unit"."s produced: $totalprod\n");		
		print ("\tNew # $nresource->unit"."s onhand: $nresource->quantity\n");
		print ("\tNet Change: ".($nresource->quantity - $oldval)."\n");
		print ("\tProducer Cost: $producercost\n");
		
		$nresource->Save($nation);
		$nation->setProp('balance',sprintf('%.0f',$nation->balance - $producercost));		
	}		
	
	print ("Original Gov't Balance: ".(number_format($old))." $nation->currency"."s\n");
	print ("Gov't Balance After Resources: ".(number_format($nation->balance))." $nation->currency"."s\n");
	$nationWriter->saveNation(array('balance',$nation->balance),$nation);		
	print ("\n");
}


$stop = microtime_float()-$start;
echo "\n--------------------------------------------\n";
echo "Processing Time: $stop seconds\n";
echo "Query Count: $query_count\n";
?>