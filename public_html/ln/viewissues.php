<?
//-----------------------------------------------------------------------------
// $RCSfile: viewissues.php,v $ $Revision: 1.7 $ $Author: addy $ 
// $Date: 2006/07/14 00:46:38 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php');
require_once('./nations.php');
require_once('./users.php');
require_once('./issues.php');
require_once('./description.php');

$userloader = new UserLoader();
$gLobbyUser = $gUser = $userloader->loadCookieUser();
$nation = null;

if ($gUser != null)
{
	$nloader = new NationLoader();
	$nation = $nloader->loadNationWithStats($gUser->countryid);
}

if ($nation==null || $gUser == null)
{
	WriteLog("WARNING:unathorized attemp to view issues");
	header("Location: $gMainURL");
}

$blurb = new Blurb();
$iLoader = new IssueLoader();

$gAction = @SafeQueryData($_REQUEST['action']);
$gStatus = "";

if ($gAction == 'savechoice')
{
	$issueid = @SafeQueryData($_REQUEST['issueid']);	
	$optionid = @SafeQueryData($_REQUEST['optionid']);	
	
	$issue = $iLoader->LoadIssue($issueid,$nation);
	
	if ($issue->options[$optionid] != null && $issue->nchoice->issue_option_id != $optionid)
	{
		// set status to modified so it will get processed in the turnjob.
		$issue->nchoice->setProp('status',ISSUE_STATUS_MODIFIED); 
		$issue->nchoice->setProp('old_issue_option_id',$issue->nchoice->issue_option_id);
		$issue->nchoice->setProp('issue_option_id',$optionid);
		
		if (!$issue->nchoice->Save())
		{
			WriteLog("Could not save issue option! nationid=[$nation->id],issueid=[$issueid],optionid=[$optionid]");
		}
		else
		{
			$option = $issue->options[$optionid];
			$gStatus = "<div class=label>Your decision has been recorded</div><br>";
		}
			
	}
	else
	{
		WriteLog("(0x1) Possible Hack Warning! nationid=[$nation->id],issueid=[$issueid],optionid=[$optionid]");
		$gStatus = "<div class=error_text>There was as error when processing your decision.</div><br>";
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States</title>

<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">
</head>

<body>
<!-- <? echo "[$gAction]"; ?>-->



<!-- BANNER CELL -->
<table class=highlight width=100% cellpadding=1 cellspacing=1>
<tr valign=top><td>
<table width=100% align=center cellspacing=0 cellpadding=0 bgcolor=#DEE7F7>
<tr><td colspan=4 align=center>
<? include "./includes/banner.inc"; ?>
</td></tr>
</table>
</td></tr>
</table>
<!-- /BANNER CELL -->

<br>

<table width=100% align=center style="height:75%">
<tr valign=top>
<!-- LEFT SIDE MENU -->
<td width=109 valign=top>
<? include "./includes/sidemenu.inc"; ?>
</td>
<!-- /LEFT SIDE MENU -->

<!-- MAIN CONTENT -->
<td valign=top>

<?
	if (strlen($gStatus) > 0)
		print $gStatus;
		
	// view an issue if the user decided to
	if ($gAction == 'viewissue')
	{
		$issue = $iLoader->LoadIssue(@SafeQueryData($_REQUEST['issueid']),$nation);

		// the user has never selected a resolution for this issue
		if ($issue != null && $issue->nchoice->nation_id == $nation->id) // && $issue->nchoice->issue_option_id == -1)
		{
			
			print ("<form name='issueoptions' action='viewissues.php' method=post>\n");
			print ("<input type=hidden name='issueid' value='$issue->issue_id'>\n");
			print ("<input type=hidden name='action' value='savechoice'>\n");			
			print ("<table width=600 class=border_table2>\n");
			print ("<tr class=label><td colspan=2><b>".($issue->GetVariableText('title'))."</b></td></tr>\n");
			print ("<tr class=label><td colspan=2>".($issue->GetVariableText('text'))."</td></tr>\n");
			print ("<tr class=label><td colspan=2>&nbsp;</td></tr>\n");

			foreach ($issue->options as $option)
			{
				$selected = "";
				if ($issue->nchoice->issue_option_id == $option->issue_option_id)
					$selected = "checked='true'";
					
				$readonly = "";					
				if (!$issue->nchoice->CanMakeNewDecision())					
					$readonly = 'disabled';
					
				print ("<tr class=label><td valign=top><input $readonly type=radio $selected name='optionid' value='$option->issue_option_id'></td><td>".($option->GetOptionText($nation))."</td></tr>\n");
				print ("<tr class=label><td colspan=2>&nbsp;</td></tr>\n");
			}			
			
			// need to determine if this issue can be modified
			if ($issue->nchoice->CanMakeNewDecision())
				print ("<tr class=label><td colspan=2 align=right><input type=submit value='Select'></td></tr>\n");
			else
				print ("<tr class=error_text><td colspan=2 align=right>You can only change your decision once per day</td></tr>\n");
				
			print ("</table>\n");
			print ("</form>\n");
			//print ("<hr width=60% align=center>\n");
			print ("<br>\n");
			
		}
		// TODO: add more checks to see if the user can MODIFY their selection
		
	}
	
	// ISSUES THAT WHERE THE USER HAS MADE NO DECISION
	$data = $DBObj->query("SELECT * FROM nation_issues WHERE (nation_id = $nation->id) AND (status = '".ISSUE_STATUS_PENDING."')");
	if ($DBObj->num_rows($data) > 0)
	{
	
		print ("<table width=600 cellpadding=1 cellspacing=1 class=border_table2>\n");
		print ("<tr class=table_title><td colspan=2><b>Issues Pending Review</b></td></tr>\n");
		print ("<tr class=row_title><td width=80%>Issue Title</td></tr>\n");
		while ($storeitem = $DBObj->fetch_array($data))
		{
			$issue = $iLoader->LoadIssue($storeitem['issue_id'],$nation);
			if ($issue == null)
				continue;

			$title = $issue->GetVariableText('title');
			$text = $issue->GetVariableText('text');
			
			print ("<tr class=gridbg1><td><a href='viewissues.php?action=viewissue&amp;issueid=$issue->issue_id'>$title</a></td></tr>\n");
		}
		print ("</table>\n");
		print ("<br>\n");
	}

	// ISSUES WAITING TO BE PROCESSED BY THE TURN JOB
	$data = $DBObj->query("SELECT * FROM nation_issues WHERE (nation_id = $nation->id) AND (status = '".ISSUE_STATUS_MODIFIED."')");
	if ($DBObj->num_rows($data) > 0)
	{
	
		print ("<table width=600 cellpadding=1 cellspacing=1 class=border_table2>\n");
		print ("<tr class=table_title><td colspan=2><b>Issues Pending Parlimentary Passage</b></td></tr>\n");
		print ("<tr class=row_title><td width=80%>Issue Title</td></tr>\n");
		while ($storeitem = $DBObj->fetch_array($data))
		{
			$issue = $iLoader->LoadIssue($storeitem['issue_id'],$nation);
			if ($issue == null)
				continue;

			$title = $issue->GetVariableText('title');
			$text = $issue->GetVariableText('text');
			
			print ("<tr class=gridbg1><td><a href='viewissues.php?action=viewissue&amp;issueid=$issue->issue_id'>$title</a></td></tr>\n");
		}
		print ("</table>\n");
		print ("<br>\n");
	}	

	//  ISSUES THAT HAVE BEEN PROCESSED BY THE TURN JOB
	$data = $DBObj->query("SELECT * FROM nation_issues WHERE (nation_id = $nation->id) AND (status = '".ISSUE_STATUS_SET."')");
	if ($DBObj->num_rows($data) > 0)
	{
	
		print ("<table width=600 cellpadding=1 cellspacing=1 class=border_table2>\n");
		print ("<tr class=table_title><td colspan=2><b>Passed Legislation</b></td></tr>\n");
		print ("<tr class=row_title><td width=80%>Issue Title</td></tr>\n");
		while ($storeitem = $DBObj->fetch_array($data))
		{
			$issue = $iLoader->LoadIssue($storeitem['issue_id'],$nation);
			if ($issue == null)
				continue;

			$title = $issue->GetVariableText('title');
			$text = $issue->GetVariableText('text');
			
		
			print ("<tr class=gridbg1><td><a href='viewissues.php?action=viewissue&amp;issueid=$issue->issue_id'>$title</a></td></tr>\n");
		}
		print ("</table>\n");
	}	
	
?>
</td>
<!-- /MAIN CONTENT -->
</tr>



</table>

</body>
</html>