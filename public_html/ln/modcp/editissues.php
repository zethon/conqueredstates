<?
//-----------------------------------------------------------------------------
// $RCSFile: editnation.php $ $Revision: 1.3 $ $Author: addy $ 
// $Date: 2006/06/19 18:27:46 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNINGS);

chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./issues.php'); 

$issueLoader = new IssueLoader();

$gAction = @SafeQueryData($_REQUEST['formaction']);

if ($gAction == 'saveissue' && @SafeQueryData($_REQUEST['issue_id']) > 0)
{
	$issue = $issueLoader->LoadIssue(@SafeQueryData($_REQUEST['issue_id']));
	
	if ($issue != null)
	{
		if ($issue->title != @SafeQueryData($_REQUEST['title']))
			$issue->setProp('title',$_REQUEST['title']);

		if ($issue->text != @SafeQueryData($_REQUEST['text']))
			$issue->setProp('text',$_REQUEST['text']);
			
		$issue->Save();
		
		foreach ($issue->options as $option)
		{
			$optext = $_REQUEST["option_text_$option->issue_option_id"];
			$optmods = $_REQUEST["option_mods_$option->issue_option_id"];
			$optphrase = $_REQUEST["option_phrase_$option->issue_option_id"];
			
			if ($optext != $option->option_text)
				$option->setProp('option_text',$optext);

			if ($optext != $option->option_phrase)
				$option->setProp('option_phrase',$optphrase);				
				
			if ($optmods != $option->mods)
				$option->setProp('mods',$optmods);
				
			$option->Save();				
							
		}
	}
}
else if ($gAction == 'newoption' && @SafeQueryData($_REQUEST['issue_id']) > 0)
{
	$newopt = new IssueOption();
	$newopt->Create(@SafeQueryData($_REQUEST['issue_id']));
}
else if ($gAction == 'newissue')
{
	$newissue = new Issue();
	$newid = $newissue->Create();
	
	if ($newid > 0)
	{
		$newopt = new IssueOption();
		$newopt->Create($newid);	
	}
}

?>

<html>
<head><title>CS: Nation Editor</title></head>

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

<body>
<? echo "<!-- action [$gAction] -->\n"; ?>

<?
	// print the issue that we're editting
	if ($gAction == 'saveissue' || $gAction == 'newoption' || $gAction == 'edit' && $_REQUEST['issue_id'] > 0)
	{
		$issueid = $_REQUEST['issue_id'];
		$issue = $issueLoader->LoadIssue($issueid);
		
		if ($issue != null)
		{
?>			
			<table width=800 cellspacing=0 cellpadding=1 bgcolor=000000 align=center>
			<tr><td>
			
			<table cellspacing=0 cellpadding=1 width=800 align=center>
			<form name=issueform method=post action=editissues.php>
			<input type=hidden name='formaction' value='saveissue'>
			<input type=hidden name='issue_id' value='<? echo $issue->issue_id; ?>'>
			<tr class=gridbg1><td><b>Issue ID:</b></td><td><? echo $issue->issue_id; ?></td></tr>
			<tr class=gridbg1><td>Title</td><td><input type=text name='title' value="<? echo $issue->title; ?>" style='width:400px'></td></tr>
			<tr class=gridbg1><td>Summary</td><td><textarea name=text cols=75 rows=3><? echo $issue->text; ?></textarea></td></tr>
			<tr><td colspan=2 class=highlight align=center>Issue Options</td></tr>
<?			
			foreach ($issue->options as $option)
			{
				print ("<tr class=gridbg1><td colspan=2><b>Issue Option ID: $option->issue_option_id</b></td></tr>\n");
				print ("<tr class=gridbg1><td colspan=2>Option Text:<br><textarea name='option_text_$option->issue_option_id' rows=3 cols=75>$option->option_text</textarea></td></tr>\n");
				print ("<tr class=gridbg1><td colspan=2>Option Phrase:<br><input type=text name='option_phrase_$option->issue_option_id' value='$option->option_phrase' style='width:600px'></td></tr>\n");
				print ("<tr class=gridbg1><td colspan=2>Mods:<br><input type=text name='option_mods_$option->issue_option_id' value='$option->mods' style='width:600px'></td></tr>\n");
				print ("<tr class=gridbg1><td colspan=2><hr color=000000></td></tr>\n");
			}	
?>		
			<tr class=gridbg1><td colspan=2 align=right><input type=button value='New Option' onClick="document.issueform.formaction.value='newoption'; submit();">&nbsp;<input type=submit value='Save'></td></tr>
			</form>
			</table>
			
			</td></tr>
			</table>
			
			<br><br>		
<?
		}
	}
?>


<?

// print all the issues in a nice pretty table
$idx = 0;
print ("<table cellspacing=0 cellpadding=0 width=800 align=center>\n");
print ("<tr><td colspan=4 class=gridbg1 align=center><b>National Issues</b></td></tr>\n");
print ("<tr class=highlight><td>ID</td><td>Title</td><td align=center># options</td><td>&nbsp;</td></tr>\n");
print ("<tr><td colspan=4><hr color=000000></td></tr>\n");
$datatable = $DBObj->query("SELECT DISTINCT issues.*, COUNT(issue_option_id) FROM issues LEFT JOIN issue_options USING (issue_id) GROUP BY (issue_options.issue_id) ORDER BY (issues.issue_id)");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$count = $storeitem['COUNT(issue_option_id)'];
	
	print ("<tr class=gridbg1><td>$storeitem[issue_id]</td><td>$storeitem[title]</td><td align=center>$count</td><td align=right><a href='editissues.php?formaction=edit&issue_id=$storeitem[issue_id]'>Edit</a>&nbsp;&nbsp;&nbsp;<a href='' onClick=\"ToogleObject('issue_text_$storeitem[issue_id]'); return false;\">View Text</a></td></tr>\n");
?>
	<tr><td colspan=4>
		<DIV ID="issue_text_<? echo $storeitem[issue_id]; ?>" style="display:none"> 
		<table width=100% cellspacing=0 cellpadding=0>
		<tr class=gridbg2><td><i><? echo $storeitem[text]; ?></i></td></tr>
		</table>
		</DIV>
	</td></tr>
	
	<tr><td colspan=4><hr color=000000></td></tr>
<?
	$idx++;	
}
print ("<tr><td colspan=4 align=right><a href='editissues.php?formaction=newissue'><input type=button value='Add Issue' onClick=\"window.location='editissues.php?formaction=newissue';\"></a></td></tr>\n");
print ("</table>\n");


?>

<? echo "<!-- Query Count: [$query_count] -->"; ?>
</body>
</html>