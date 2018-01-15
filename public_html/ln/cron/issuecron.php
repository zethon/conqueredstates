<?php 
//-----------------------------------------------------------------------------
// $RCSfile: issuecron.php,v $ $Revision: 1.7 $ $Author: addy $ 
// $Date: 2006/06/22 22:12:12 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./description.php');
require_once('./issues.php');

define('MAX_NUM_ISSUES',3);


define('CVS_REVISION', '$RCSfile: issuecron.php,v $ - $Revision: 1.7 $');
if (defined('CVS_REVISION'))
{
	$re = '#^\$' . 'RCS' . 'file: (.*\.php),v ' . '\$ - \$' . 'Revision: ([0-9\.]+) \$$#siU';
	$cvsversion = preg_replace($re, '\1, CVS v\2', CVS_REVISION);
}

global $querytime, $query_count;
$start = microtime_float(); // start the timer

$nationLoader = new NationLoader();
$nationWriter = new NationWriter();
$issueLoader = new IssueLoader();
$modtokenizer = new TextModTokenizer();

$nations = array(); 		// a hash of nations so we don't have reload already loaded nations
$dirtynats = array();		// an array of 'dirty' nation ids, the ones we have to save

// print the header and report info
header("Content-Type: text/plain");
print ("File  : $cvsversion\n");
print ("Server: $gGameServerName\n");
print ("Date  : ".(date('d-m-Y H:i:s'))."\n");
print ("--------------------------------------------\n\n");

// first, take care of the issues that have been modified
$datatable = $DBObj->query("SELECT * FROM nation_issues WHERE (status = ".(ISSUE_STATUS_MODIFIED).")");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$nchoice = new NationIssueChoice($storeitem);
	$issue = $issueLoader->LoadIssue($nchoice->issue_id);
	
	if ($issue == null)
		continue;
	
	// this saves us a few queries when dealing with multiple queries	
	if ($nations[$nchoice->nation_id] == null)
	{
		$nation = $nationLoader->loadNationWithStats($nchoice->nation_id,false);
		
		if ($nation == null)
			continue;
		else
			$nations[$nation->id] = $nation;
	}
	else
		$nation = $nations[$nation->id];
	
	if ($nation == null)
		continue;

	// verify that the option we're going to implement is valid		
	$option = $issue->options[$nchoice->issue_option_id];		
	if ($option == null || strlen($option->mods) == 0)
		continue;

	print ("** Nation: $nation->name ($nation->id) Issue: $issue->issue_id\n");
	// undo the old issue_option if need be
	if ($nchoice->old_issue_option_id != 0 && isset($issue->options[$nchoice->old_issue_option_id]))
	{
		$oldoption = $issue->options[$nchoice->old_issue_option_id];
		$modifiers = $modtokenizer->Tokenize($oldoption->mods);
	
		if (count($modifiers) > 0)
		{
			print ("Undoing Old Option: [$nchoice->old_issue_option_id]\n");
			print ("\tMods: [$oldoption->mods]\n");
			foreach ($modifiers as $modifer)
			{
				$modifer->UndoModify(&$nation);
			}	
		}			
	}
	
	// now process the option
	$option = $issue->options[$nchoice->issue_option_id];
	$modifiers = $modtokenizer->Tokenize($option->mods);
	
	if (count($modifiers) > 0)
	{
		print ("Processing New Option: [$nchoice->issue_option_id]\n");
		print ("\tMods: [$option->mods]\n");
		foreach ($modifiers as $modifer)
		{
			$modifer->DoModify(&$nation);
		}	
		
		// save the issue data
		$nchoice->setProp('status',ISSUE_STATUS_SET); 
		$nchoice->Save(false);				
		
		// add the nation and info the nation buffer
		$nations[$nation->id] = $nation;
		$dirtynats[$nation->id] = $nation->id;
	}
}

// save the "dirty" nations in the nation buffer
if (count($dirtynats) > 0)
{
	print ("\nSaving Modified Nations...\n");
	foreach ($dirtynats as $natid)
	{
		if ($nations[$natid] != null)
		{
			$nation = $nations[$natid];
			print ("Saving $nation->name ($nation->id)\n");
			$nation->stats->Save();
			$nation->expenses->Save();
		}
	}
}
else
{
	print ("No National Issues to Save...\n");
}

print ("\n");

// ASSIGN NEW ISSUES TO NATIONS
class NationInfo // helper class
{
	var $nation_id = 0;
	var $issue_count = 0;
	var $issue_id_array = array();
}

// build an arry of all possible issue ids
$issueids = array();
$datatable = $DBObj->query("SELECT DISTINCT issues.issue_id FROM issues LEFT JOIN  issue_options USING (issue_id) WHERE (issue_option_id IS NOT NULL) AND (mods != '')");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	array_push($issueids,$storeitem['issue_id']);	
}


// build an array of all the nation info in regards to how many issues they are assigne and which ones
$natinfos = array();
$datatable = $DBObj->query("SELECT DISTINCT nation.*,nation_issues.status,nation_issues.issue_id FROM nation LEFT JOIN nation_issues USING (nation_id) LEFT JOIN nation_regions ON (nation_regions.nation_id = nation.nation_id)");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	if ($natinfos[$storeitem['nation_id']] == null)
	{
			$natinfo = new NationInfo();
			$natinfo->nation_id = $storeitem['nation_id'];
			$natinfos[$storeitem['nation_id']] = $natinfo;
	}

	if ($storeitem['status'] == "0" && $storeitem['issue_id'] > 0)
	{
		$natinfo = $natinfos[$storeitem['nation_id']];
		$natinfo->issue_count++;
		$natinfo->issue_id_array[$storeitem['issue_id']] = 1;
		$natinfos[$storeitem['nation_id']] = $natinfo;
	}
	else if ($storeitem['status'] != "0" && $storeitem['issue_id'] > 0)
	{
		$natinfo = $natinfos[$storeitem['nation_id']];
		$natinfo->issue_id_array[$storeitem['issue_id']] = 1;
		$natinfos[$storeitem['nation_id']] = $natinfo;		
	}
}

// loop through all the nations and assign up to MAX_NUM_ISSUES issues
foreach ($natinfos as $natinfo)
{
	if ($natinfo->issue_count < MAX_NUM_ISSUES)
	{
		//TODO: Investigate infinite loop condition (when there aren't enough issues)
		for ($i = 0; ($natinfo->issue_count + $i) < MAX_NUM_ISSUES; $i++)
		{
			$localissids = $issueids;
			$count = count($localissids);
			$step = 0; $done = false;
			while (!$done)
			{
				$idx = rand(0,count($localissids)-1);
				$newissid = $localissids[$idx];					
					
				if ($natinfo->issue_id_array[$newissid] != 1)					
				{
					$issopt = new NationIssueChoice();
					$newnatissid = $issopt->Create($natinfo->nation_id,$newissid);
					if ($newnatissid > 0)
					{
						print ("Nation ($natinfo->nation_id) Assigned IssueID ($newissid) as nation_issue_id ($newnatissid)\n");
						$natinfo->issue_id_array[$newissid] = 1;
						$done = true;
					}
				}
					
				array_splice($localissids,$idx,1);
				$step++;
				if ($step >= $count)
					$done = true;
			}
		}		
	}
}


$stop = microtime_float()-$start;
echo "\n--------------------------------------------\n";
echo "Processing Time: $stop seconds\n";
echo "Query Count: $query_count\n";

?>