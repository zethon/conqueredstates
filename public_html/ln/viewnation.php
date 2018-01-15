<?
//-----------------------------------------------------------------------------
// $RCSFile: viewnation.php $ $Revision: 1.26 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php');
require_once('./nations.php');
require_once('./maps.php');
require_once('./users.php');
require_once('./description.php');

function GetGoodness($val)
{
	$retval = "Unknown";
	
	if ($val < 1)
		$retval = "Abysmal";
	else if ($val < 2)
		$retval = "Very Poor";
	else if ($val < 3)
		$retval = "Poor";
	else if ($val < 4)
		$retval = "Below Average";
	else if ($val < 7)
		$retval = "Average";
	else if ($val < 8)
		$retval = "Above Average";
	else if ($val < 9)
		$retval = "Excellent";
	else if ($val <= 10)
		$retval = "Amazing";
		
	return $retval;		
}

$userloader = new UserLoader();
$gUser = $userloader->loadCookieUser();
$nation = null;


if (strlen($_REQUEST['nationname']) > 0)
{
	$name = @SafeQueryData($_REQUEST['nationname']);
	$data = $DBObj->query_first("SELECT nation_id FROM nation WHERE (name = '$name')");
	$countryid = $data['nation_id'];	
}
else
	$countryid = $_REQUEST['nationid']; // nationid sent from the image map generated in worldmap.php

if (intval($countryid) && $countryid != "")
{
	$loader = new NationLoader();
	$nation = $loader->loadNationWithStats($countryid);	

	if ($nation->id > 0)
	{
		$string = "";
		foreach ($nation->regions as $region)
		{
			foreach ($region->coordsArray as $coords)
			{
				if (strlen($string) > 0)
					$string .= ",";
					
				$string .= @implode(",",$coords);
			}
		}	
		list($src_x,$src_y,$src_w,$src_h) = get_zoom_map(explode(",",$string));
	}
	else
	{
		header("Location: $gMainURL/viewnation.php");
		exit;
	}
}

$datatable = $DBObj->query("SELECT DISTINCT nation.nation_id,nation.name,nation_titles.nation_title_text  FROM nation LEFT JOIN nation_regions USING (nation_id) LEFT JOIN nation_titles ON (nation.nation_title = nation_titles.nation_title_id) ORDER BY nation.name");
if ($DBObj->num_rows($datatable) > 0)
{
	$dropdowntxt = "<select name='nationid'>\n";

	$i = 0;
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$id = $storeitem["nation_id"];
		$name = $storeitem["name"];	
		$title = 	$storeitem["nation_title_text"];	
		
		$selected = "";
		if ($countryid == $id)
			$selected = "selected=true";
		
		$dropdowntxt .= "<option $selected value='$id'>The $title of ".(ucfirst($name))."</option>\n";				
		
		$i++;			
	}
	$dropdowntxt .= "</select>\n";			
}

if ($CSCollapsed['nationmap'])
{
	$buttontxt = "Show Map";
	$inivistr = "STYLE=\"display:none\"";
}
else
	$buttontxt = "Hide Map";
	
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv=Content-Type content="text/html; charset=utf-8" />
<title>Conquered States: View Nation</title>

<link rel="stylesheet" href="css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script type="text/javascript">
<!--
var mapWindow = null;
<? if ($_REQUEST[mapwnd] != 'closed') { ?>
mapWindow = open('','nationmap',"top=-20,left=-20,height=1,width=1,visible=false");
if (mapWindow.location == 'about:blank')
{
	// sanity check
	mapWindow.close();
	mapWindow = null;
}
<? } ?>
		
function DoMapPopup(obj) 
{
	if (mapWindow == null)
    mapWindow = window.open("<? echo $gMainURL; ?>/popups/nationsmap.php","nationmap","height=400,width=400,scrollbars=yes,resizable=yes")

 		mapWindow.focus();
 		scrollWindow(<? echo floor($src_x+($src_w/2)); ?>,<? echo floor($src_y+($src_w/2)); ?>,mapWindow);
}

function DoLoad()
{
	if (mapWindow)
		scrollWindow(<? echo floor($src_x+($src_w/2)); ?>,<? echo floor($src_y+($src_w/2)); ?>,mapWindow);
}

function DoDropdown()
{
	if (mapWindow != null)
		document.nationfrm.mapwnd.value = 'open';
		
	document.nationfrm.submit();
}
-->
</script>
</head>

<body onLoad="DoLoad();">
<? echo "<!-- [".$_REQUEST['mapwnd']."] -->"; ?>


<!-- BANNER CELL -->
<table width=100% align=center cellspacing=0 cellpadding=0 class="border_table">
<tr><td colspan=4 align=center>
<? include "./includes/banner.inc"; ?>
</td></tr>
</table>
<!-- /BANNER CELL -->

<br>

<table width=100% align=center>
<tr valign=top>

<!-- LEFT SIDE MENU -->
<td width=109 valign=top>
<? include "./includes/sidemenu.inc"; ?>
</td>
<!-- /LEFT SIDE MENU -->

<!-- MAIN CONTENT -->
<td valign=top>

<!-- NATION INFO -->
<?
	if ($nation != null)
	{
		$nation->CalcEconomicStats();
		$desc = new NationDesc();		
?>
<table width=100% align=center>

<tr><td align=left><img src="viewflag.php?id=<? echo $nation->flagid; ?>" alt="<? echo $nation->name; ?> National Flag" border=1></td><td valign=top class=nation_banner_text align=center><? echo "The ".$nation->title." of ".ucfirst($nation->name)."<br><div class=label>\"$nation->motto\"</div>"; ?></td></tr>

<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 align=center class=label>Map of <? echo ucfirst($nation->name); ?><br><img border=1 src="<? echo "viewmap.php?action=viewnation&amp;id=".$nation->id; ?>" alt="<? echo $nation->name; ?> National Map"></td></tr>

<tr><td colspan=2><hr></td></tr>



<tr><td colspan=2><table align=center width=85%><tr><td class=label><b>Overview</b><br><? echo $desc->GetGenericDescription($nation); ?></td></tr></table></td></tr>

<tr><td colspan=2><hr></td></tr>

<tr><td colspan=2 width=100%>
<table width=100%>
<tr class=label align=center><td><b>Economy:</b> <? echo GetGoodness(($nation->stats->economic_strength+$nation->stats->economic_health)/2); ?></td><td><b>Civil Liberties:</b> <? echo GetGoodness($nation->stats->civil_liberties); ?></td><td><b>Political Freedom: </b><? echo GetGoodness($nation->stats->political_freedom); ?></td></tr>
</table>
</td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 width=100%>
<table width=100%>
<tr class=label align=center valign=top><td><b>GDP</b> (in <? echo $nation->currency; ?>):</b><br>&nbsp;<? echo $nation->currency_symbol; ?><? echo number_format($nation->ecstats->gdp); ?></td><td><b>GDP per Capita</b> (in <? echo $nation->currency; ?>):<br>&nbsp;<? echo $nation->currency_symbol; ?><? echo number_format($nation->ecstats->gdppc); ?></td><td><b>Exchange Rate:</b><br><? echo $nation->currency_symbol; ?>1 <? echo $nation->currency; ?> = $<? echo number_format($nation->ecstats->exchrate,2, '.', ','); ?> USD<br><? echo $nation->currency_symbol; ?><? echo number_format(1/$nation->ecstats->exchrate, 2, '.', ',')." ".$nation->currency; ?> = $1.00 USD</td></tr>
</table>
</td></tr>

<tr><td colspan=2><hr></td></tr>

<tr><td colspan=2><b class=label>Link to this page: <a class=link href="<? echo "$gMainURL/".(strtolower($nation->name)); ?>"><? echo "$gMainURL/".(strtolower($nation->name)); ?></a></b></td></tr>
</table>

<hr>
<br>

<!--<tr><td>Population:</td><td><? echo number_format($nation->GetPopulation()); ?></td></tr>
<tr><td>Motto:</td><td><? echo $nation->motto; ?></td></tr>
<tr><td>Currency:</td><td><? echo $nation->currency; ?></td></tr>
<tr><td>TaxRate:</td><td><? echo ($nation->taxrate * 100); ?>%</td></tr>
<tr><td colspan=2><hr></td></tr>-->
<!--<tr class=label><td>Gov't Effeciency:</td><td><? echo number_format($nation->ecstats->govteff * 100); ?>%</td></tr>-->
<!--<tr class=label><td>Gov't Waste:</td><td><? echo number_format($nation->ecstats->govtwaste); ?></td></tr>-->
<!--<tr class=label><td>Unemployment:</td><td><? echo number_format($nation->ecstats->unemployment*100); ?>%</td></tr>-->
<!--<tr class=label><td>Consumer Confidence:</td><td><? echo number_format($nation->ecstats->consconf*100); ?>%</td></tr>-->
<!--<tr class=label><td><b>Consumption:</b></td><td><? echo number_format($nation->ecstats->consumption); ?></td></tr>-->
<!--<tr class=label><td><b>Gov't Budget:</b></td><td><? echo number_format($nation->ecstats->govtbudg); ?></td></tr>-->

<?		
	}
?>
<!-- /NATION INFO -->



<!-- NATION SELECTOR -->
<form name=nationfrm method=post action=viewnation.php>	
<input type=hidden name=mapwnd value=closed>
<table class="border_table" width=100%>
<tr><td colspan=2><b>Select Nation</b></td></tr>
<tr><td><? echo $dropdowntxt; ?><input type=button value="Go" onClick='javascript:DoDropdown(this);'></td><td align=right><input type=button onClick="javascript:DoMapPopup(this);" value='Show World Map' id='maptext'></td></tr>
</table>
</form>
<!-- /NATION SELECTOR -->





<!-- /MAIN CONTENT -->
</td>
</tr>
</table>


<? echo "<!-- qc:[$query_count] -->"; ?>
</body>
</html>	