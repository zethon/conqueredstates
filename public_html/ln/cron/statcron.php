<?php 
//-----------------------------------------------------------------------------
// $RCSfile: statcron.php,v $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/15 20:26:55 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('./../');
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./nations.php');
require_once('./resources.php');
require_once('./issues.php');

define('CVS_REVISION', '$RCSfile: statcron.php,v $ - $Revision: 1.2 $');
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

// process all the nations that have at least one region 
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
	
	$oldnationpop = $nation->GetPopulation();
	$growthrate = $nation->GetGrowthRate();
	print ("Current Population: ".(number_format($oldnationpop))."\n");
	print ("Growth Rate: ".(number_format($growthrate*100,2))."%\n");
	
	// calculate new populations by region
	$idx = 0;
	foreach ($nation->regions as $region)
	{
		print ("Region: $region->name ($region->id)\n");
		print ("\tOld Population: $region->population\n");
		
		// calculate the new population and the net change in people
		$newregpop = ceil($region->population + (($region->population * $growthrate)/12));
		$change = $newregpop - $region->population;
		
		// update the region and nation objects
		$region->setProp('population',$newregpop);
		$nation->regions[$idx] = $region;
		
		$regionWriter->saveRegion($region);
		print ("\tNew Population: $region->population\n");
		print ("\tNet Population Change: $change\n");		
		$idx++;
	}
	print ("New National Population: ".$nation->GetPopulation()."\n");
	print ("\n");
	
	// recalc the stats
	$old = $nation->balance;
	$nation->CalcEconomicStats();
	$govintake = sprintf('%.0f',$nation->ecstats->govtbudg/365);
	$nation->setProp('balance',sprintf('%.0f',$nation->balance + $govintake));
	
	print ("Tax Rate: ".(number_format($nation->taxrate*100,2))."%\n");
	print ("Gov't Intake: ".(number_format($govintake))." $nation->currency"."s\n");
	
	foreach ($nation->expense_names as $name)
	{
		if ($nation->expenses->$name > 0)
		{
			$temp = sprintf('%.0f',(($nation->ecstats->govtbudg/365) * $nation->expenses->$name));
			print ("Expense: $name, Daily Budget: ".(number_format($temp))." $nation->currency"."s\n");
			$nation->setProp('balance',sprintf('%.0f',$nation->balance - $temp));
		} 
		// TODO: generate issues/news		
	}
	
	print ("Original Gov't Balance: ".(number_format($old))." $nation->currency"."s\n");
	print ("Gov't Balance After Expenses: ".(number_format($nation->balance))." $nation->currency"."s\n");
	print ("\n");
	
	// TODO: modify the nation's stats	
	
	$nationWriter->saveNation(array('balance',$nation->balance),$nation);
	$nation->stats->Save();
}

$stop = microtime_float()-$start;
echo "\n--------------------------------------------\n";
echo "Processing Time: $stop seconds\n";
echo "Query Count: $query_count\n";


?>