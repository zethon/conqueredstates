<?
//-----------------------------------------------------------------------------
// $RCSFile: viewissues.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/06/19 18:27:46 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./nations.php');
require_once('./users.php');

$userloader = new UserLoader();
$gLobbyUser = $gUser = $userloader->loadCookieUser();
$nation = null;

if ($gUser != null)
{
	$nloader = new NationLoader();
	$nation = $nloader->loadNationWithStats($gUser->countryid,false);
}

if ($nation==null || $gUser == null)
{
	WriteLog("WARNING:unathorized attemp to view settings");
	header("Location: $gMainURL");
}

if ($_REQUEST['formaction'] == 'savenation')
{
	$errorstr = "";
	$updatestr = "";
	
		if (strlen($_REQUEST['currency']) == 0)
		$errorstr .= "C";
		
	if (strlen($_REQUEST['motto']) == 0)
		$errorstr .= "D";
	
	if (strlen($errorstr) == 0)
	{
		if ($nation->titleid != $_REQUEST['title'])
		{
			$updatestr.= "nation_title='".(@SafeQueryData($_REQUEST['title']))."',";
		}
			
		if ($nation->flagid != $_REQUEST['flag'] && $_REQUEST['flag'] != "-1")
		{
			$flagid = @SafeQueryData(substr($_REQUEST['flag'],0,strpos($_REQUEST['flag'],";")));
			$data = $DBObj->query_first("SELECT type FROM nation_flags WHERE (flagid = '$flagid')");
			
			// the user is selecting a pre-stored flag and they had a custom one, so delete the custom flag
			if ($nation->customflag && $data['type'] == "0")
				$DBObj->query("DELETE FROM nation_flags WHERE (flagid = '$nation->flagid' AND type = '1')");
				
			$updatestr .= "flag='$flagid',";		
		}
		
		if ($nation->currency != $_REQUEST['currency'])
			$updatestr .= "currency='".$_REQUEST[currency]."',";
			
		if ($nation->motto != $_REQUEST['motto'])
			$updatestr .= "motto='".$_REQUEST[motto]."',";		
			
		if ($nation->currency_id != $_REQUEST['currency_symbol'])
			$updatestr .= "currency_id='".$_REQUEST[currency_symbol]."',";		
	
		if (strlen($updatestr) > 0)
		{
			if (substr($updatestr,strlen($updatestr)-1,1) == ",")
				$updatestr{strlen($updatestr)-1} = " ";
				
			$query = "UPDATE nation SET $updatestr WHERE (nation_id = '$nation->id')";
			//print ("<h1>[$query]</h1>");
			$DBObj->query($query);
			if ($DBObj->affected_rows() >= 1)
			{
				$nation = $nloader->loadNationWithStats($gUser->countryid,false);
				//header("Location: $gMainURL");
				$gStatus = "<b class=label><strong>*** Changes saved</strong></b><hr>";
			}
			else
				$gStatus = "<div class=error_text>Unable to save data</div>\n<!-- q [$query] -->\n<hr>";				
		}
	}	
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States</title>

<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">

<script type="text/javascript">
<!--
function flagPopup() 
{
    winObject = window.open("<? echo $gMainURL; ?>/popups/flagupload.php","flagForm","height=240,width=400")
    winObject.focus();
}

function updateFlag(obj)
{ 
	var val =  obj.options[obj.selectedIndex].value;
	
	if (val != -1)
	{
		var imgurl = "<? echo $gImagesURL; ?>/flags/"+val.substring(val.indexOf(';')+1);
  	document.flagimg.src = imgurl;
  }
	else
<? if ($nation->customflag) { ?>
	{
  	document.flagimg.src = "viewflag.php?id=<? echo $nation->flagid; ?>";
	}
<? } else { ?>
	{
		flagPopup();
	}
<? } ?>
		
}
-->
</script>
</head>

<body>

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
	for($i=0;$i<strlen($errorstr);$i++)
		$errorHash{$errorstr{$i}} = true;

	if (strlen($gStatus) > 0) 
		print "<b>$gStatus</b>"; 
		
	if ($nation->customflag)
		$selid = -1;
	else
		$selid = $nation->flagid;			
?>

<form method=post action=viewsettings.php name=mainform>
<input type=hidden name=formaction value=savenation>
<table>

<tr class=gridbg1>
	<td><b>Nation Name:</b></td><td>The <? printTitleDropDown1($nation->titleid); ?> of <b><? echo ucfirst($nation->name); ?></b></td>
</tr>

<tr class=gridbg1>
	<td valign=top><b>National Flag:</b></td><td valign=top><img alt="<? echo $nation->name; ?> National Flag" name="flagimg" src="viewflag.php?id=<? echo $nation->flagid; ?>"><br><br>[<a href="#" onClick="flagPopup();return false;">upload your own</a>]&nbsp;<b>or</b>&nbsp; Choose: <? printFlagDropDown($selid,"flag","onChange='updateFlag(this);'",true); ?></td>
</tr>

<tr><td>&nbsp;</td></tr>


<tr class=gridbg1><td><b>Currency Symbol:</b></td><td><? printCurrencySymbolDropDown($nation->currency_id); ?></td></tr>

<tr class=gridbg1><td><b>Currency Name:</b></td><td><input type=text name=currency maxlength=20 value='<? echo htmlspecialchars($nation->currency,ENT_QUOTES); ?>'>
<? if ($errorHash{'C'}) { ?>
	<font class=error_text>Your citizens must know what to call their unit of currency!</font>
<? } ?>
</td></tr>

<tr class=gridbg1><td><b>National Motto:</b></td><td><input type=text name=motto maxlength=75 value='<? echo htmlspecialchars($nation->motto,ENT_QUOTES); ?>' style="width:250px">
<? if ($errorHash{'D'}) { ?>
	&nbsp;<font class=error_text>The people of your nation demand a motto!</font>
<? } ?>
</td></tr>


<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2><input type=submit value="Save Settings"></td></tr>
</table>
</form>


</td>
<!-- /MAIN CONTENT -->
</tr>



</table>

</body>
</html>