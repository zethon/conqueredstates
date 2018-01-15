<?php 
//-----------------------------------------------------------------------------
// $RCSfile: balanceissues.php,v $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/24 01:50:57 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./description.php');
require_once('./issues.php');
require_once('./nations.php');

$issueLoader = new IssueLoader();
$nationLoader = new NationLoader();
$modtokenizer = new TextModTokenizer();

// build the nation dropdown

function getNationDropDown($selid = -1)
{
	$DBObj = $GLOBALS['DBObj'];
	
	$datatable = $DBObj->query("SELECT DISTINCT nation.nation_id,nation.name,nation_titles.nation_title_text  FROM nation LEFT JOIN nation_regions USING (nation_id) LEFT JOIN nation_titles ON (nation.nation_title = nation_titles.nation_title_id) ORDER BY nation.name");
	if ($DBObj->num_rows($datatable) > 0)
	{
		$dropdowntxt = "<select name='tnid'>\n";
	
		$i = 0;
		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			$id = $storeitem["nation_id"];
			$name = $storeitem["name"];	
			$title = 	$storeitem["nation_title_text"];	
			
			$selected = "";
			if ($selid == $id)
				$selected = "selected=true";
			
			$dropdowntxt .= "<option $selected value='$id'>The $title of ".(ucfirst($name))."</option>\n";				
			
			$i++;			
		}
		$dropdowntxt .= "</select>\n";			
	}
	
	return $dropdowntxt;
}

?>

<html>
<head><title>CS: Issue Balancing</title>

<link rel="stylesheet" href="/css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script>
function ToogleObject(objname)
{
	if (!DHTML) return;

	var x = new getObj(objname);
	var flag = (x.style.display == 'none');
	
	x.style.display = (flag) ? '' : 'none';
	
	return flag;
}
</script>

</head>
<body>

<?

if ($_REQUEST['formaction'] == 'balance')
{
	$tnid = @SafeQueryData($_REQUEST['tnid']);
	if ($tnid == '')
		$tnid = 1;

	$dropdown = getNationDropDown($tnid);		
	$newnation = $nation = $nationLoader->LoadNation($tnid);
	$optid = $_REQUEST['option_id'];
	$data = $DBObj->query_first("SELECT * FROM issue_options WHERE (issue_option_id = '$optid')");
	
	if ($data['issue_option_id'] > 0)
	{
		$option = new IssueOption($data);
		$issue = $issueLoader->LoadIssue($option->issue_id,$nation);

		// get the mod string		
		$modstr = preg_replace('/\r\n/',';',$_REQUEST['mods']);
		if (strlen($modstr) == 0)
			$modstr = $option->mods;
		
		// apply the modifiers
		$modifiers = $modtokenizer->Tokenize($modstr);
		foreach ($modifiers as $modifer)
			$modifer->DoModify(&$newnation);

		// go ahead and save now if that's what we're doing
		if ($_REQUEST['dosave'] == 'true')
		{
			$option->setProp('mods',$modstr);
			$option->Save();
		}

		// prepare the modstr for the text area
		$modstr = str_replace(";","\r\n",$modstr);
				
		print ("<form method=post action=balanceissues.php name=option_form>\n");
		print ("<input type=hidden name=formaction value='balance'>\n");						
		print ("<input type=hidden name=option_id value='$optid'>\n");
		print ("<input type=hidden name=dosave value=''>\n");

		print ("<table align=center width=800 class=border_table>\n");
		print ("<tr><td colspan=4 class=gridbg1><b>".($issue->GetVariableText('title'))." ($issue->issue_id)</b><br>".($issue->GetVariableText('text'))."</td</tr>\n");
		print ("<tr><td colspan=4 >&nbsp;</td></tr>\n");
		print ("<tr><td colspan=4  class=gridbg1><b>Issue Option ID:</b>&nbsp;$option->issue_option_id<br>".($option->GetOptionText($nation))."</td></tr>\n");
		print ("<tr><td colspan=4 >&nbsp;</td></tr>\n");
		print ("<tr class=gridbg1><td colspan=4  colspan=2><b>Mods:</b><br><textarea name=mods rows='10' cols='60' WRAP=SOFT>$modstr</textarea></td></tr>\n");
		print ("<tr><td colspan=4><input type=submit value='Recalculate'>&nbsp;&nbsp;&nbsp;&nbsp;<input type=button value='Save Mods' onClick=\"document.option_form.dosave.value='true'; submit();\"></td></tr>\n");
		print ("<tr><td colspan=4 ><hr></td></tr>\n");
		
		print ("<tr><td colspan=4 class=gridbg1><b>Test Nation:</b>&nbsp;".(ucfirst($nation->name))." ($nation->id)&nbsp;&nbsp;$dropdown</td></tr>\n");
		print ("<tr><td colspan=4 >&nbsp;</td></tr>\n");
		print ("<tr class=highlight><td colspan=2 align=center width=50%>Before</td><td colspan=2 align=center>After</td><tr>\n");
		
			
		$vars = get_object_vars($nation->stats);
		foreach ($vars as $name=>$val) 
		{				
			if ($name == 'statsid')
				continue;
				
			$aclass = "";
			if ($nation->stats->$name > $newnation->stats->$name)
				$aclass = "style='color:red;font-weight: bold;'";
			else if ($nation->stats->$name < $newnation->stats->$name)
				$aclass = "style='color:green;font-weight: bold;'";
				
			print ("<tr class=gridbg1><td><b>stats:$name:</b></td><td>".($nation->stats->$name)."</td><td $aclass><b>stats:$name:</b></td><td $aclass>".($newnation->stats->$name)."</td></tr>\n");	
		}
	
		print ("<tr><td colspan=4 >&nbsp;</td></tr>\n");
				
		$vars = get_object_vars($nation->expenses);
		foreach ($vars as $name=>$val) 
		{				
			if ($name == 'nation_expense_id')
				continue;			
	
			$aclass = "";
			if ($nation->expenses->$name > $newnation->expenses->$name)
				$aclass = "style='color:red;font-weight: bold;'";
			else if ($nation->expenses->$name < $newnation->expenses->$name)
				$aclass = "style='color:green;font-weight: bold;'";
							
			print ("<tr class=gridbg1><td><b>expenses:$name:</b></td><td>".($nation->expenses->$name)." (".(number_format($nation->expenses->$name*100))."%)</td><td $aclass><b>expenses:$name:</b></td><td $aclass>".($newnation->expenses->$name)." (".(number_format($newnation->expenses->$name*100))."%)</td></tr>\n");	
			
		}

		print ("</table>\n");
		print ("</form>\n");
	}	
	
	print ("<br><br>\n");
}


// print all the issues in a nice pretty table
$issues = array();
$idx = 0;
print ("<table cellspacing=0 cellpadding=0 width=800 align=center class=border_table>\n");
print ("<tr class=highlight><td>ID</td><td>Title</td><td align=center># options</td><td>&nbsp;</td></tr>\n");
print ("<tr><td colspan=4><hr color=000000></td></tr>\n");
$datatable = $DBObj->query("SELECT * FROM issues LEFT JOIN issue_options USING (issue_id) ORDER BY (issue_options.issue_id)");

while ($storeitem = $DBObj->fetch_array($datatable))
{
	$issue_id = $storeitem['issue_id'];
	
	if (!isset($issues[$issue_id]))
	{
		$temp =& new Issue($storeitem);
		$issues[$issue_id] = $temp;		
	}

	$opt =& new IssueOption($storeitem);
	
	if ($opt->issue_option_id > 0)
	{
		$iss =& $issues[$issue_id];
		$iss->options[$opt->issue_option_id] =& $opt;
	}
}

foreach ($issues as $issue)
{
	$count = count($issue->options);
	print ("<tr class=gridbg1><td>$issue->issue_id</td><td>$issue->title</td><td align=center>$count</td><td align=right><a href='editissues.php?formaction=edit&amp;issue_id=$issue->issue_id'>Edit</a>&nbsp;&nbsp;&nbsp;<a href='#' onClick=\"ToogleObject('issue_text_$issue->issue_id'); return false;\">View Options</a></td></tr>\n");
?>
	<tr><td colspan=4 align=left>
		<DIV ID="issue_text_<? echo $issue->issue_id; ?>" style="display:none"> 
		<table class=gridbg2 width=100%>
		<tr><td width=10%><b>Option ID</b></td><td colspan=2><b>Option Mods</b></td></tr>
<?
		if (isset($tnid))
			$tnidstr = "&amp;tnid=$tnid";
			
		foreach ($issue->options as $option)
		{
			$txt = $option->mods;
			if (strlen($txt) == 0)
				$txt = "-not set-";
				
			print ("<tr><td align=center>$option->issue_option_id</td><td>&nbsp;</td><td align=left>$txt</td><td align=right><a href='balanceissues.php?formaction=balance&amp;option_id=$option->issue_option_id$tnidstr'>Balance</a></td></tr>\n");
			print ("<tr><td>&nbsp;</td><td colspan=3><i>$option->option_text</i></td></tr>\n");
		}	
?>		
		</table>
		</DIV>
	</td></tr>

<?
}
print ("</table>\n");


?>

<? echo "<b>Query Count: [$query_count]</b>"; ?>
</body>
</html>