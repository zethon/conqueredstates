<?
//-----------------------------------------------------------------------------
// $RCSfile: join.php,v $ $Revision: 1.10 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/global.php');
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./nations.php');
require_once('./users.php');
require_once('./maps.php');

if (!@isValidLobbyUser($_COOKIE[$gCookieLogin],$_COOKIE[$gCookiePassword]))
{
	header ("Location: $gLobbyURL?notlobbyuser=".$_COOKIE[$gCookieLogin]."&".$_COOKIE[$gCookiePassword]);
	exit;
}

$userloader = new UserLoader();
if ($userloader->loadUser($_COOKIE[$gCookieLogin]) != null)
{
	header ("Location: $gMainURL/");
	exit;
}

$regionid = $_REQUEST['regionid'];

if (intval($regionid) && $regionid != "")
{
	
	$regloader = new RegionLoader();
	$region = $regloader->loadRegion($regionid);
		
	if ($region != null)
	{
		// get the map coords
		$string = "";
		foreach ($region->coordsArray as $coords)
		{
			if (strlen($string) > 0)
				$string .= ",";
			
			$string .= @implode(",",$coords);
		}		
		list($src_x,$src_y,$src_w,$src_h) = get_zoom_map(explode(",",$string));		

		setcookie($gJoiningCountry,$regionid,$expTime);
	}	
}


$datatable = $DBObj->query("SELECT DISTINCT regions.region_id,regions.name FROM regions LEFT JOIN region_coords USING (region_id) LEFT JOIN nation_regions USING (region_id)WHERE (region_coords.region_id IS NOT NULL) AND (nation_id IS NULL) ORDER BY name");
if ($DBObj->num_rows($datatable) > 0)
{
	$dropdowntxt = "<select name='regionid' onChange='javascript:submit()'>\n";

	$i = 0;
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$id = $storeitem["region_id"];
		$name = $storeitem["name"];	

		$selected = "";
		if ($regionid == $id)
			$selected = "selected=true";
		
		$dropdowntxt .= "<option $selected value='$id'>$name</option>\n";				
		
		$i++;			
	}
	$dropdowntxt .= "</select>\n";			
}

if ($CSCollapsed['joinmap'])
{
	$buttontxt = "Show Map";
	$inivistr = "STYLE=\"display:none\"";
}
else
	$buttontxt = "Hide Map";
?>

<html>
<head>
</head>

<link rel="stylesheet" href="css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script>
function DoToggle(objname,thisobj)
{
	 var vis = ToogleVisible(objname);
	 if (vis) 
	 {
	 		thisobj.value = 'Hide Map'; 
	 		doScroll(<? echo floor($src_x+($src_w/2)); ?>,<? echo floor($src_y+($src_w/2)); ?>,'mapiframe');
	 	} 
	 	else 
	 		thisobj.value = 'Show Map';
}
</script>


<body onLoad="doScroll(<? echo floor($src_x+($src_w/2)); ?>,<? echo floor($src_y+($src_w/2)); ?>,'mapiframe');">
<? echo "<!-- region [$regionid]\n -->\n"; ?>
<center>
<hr width=800><font class=banner_text>Conquered States</font><hr width=800>
<table width=800 cellpadding=0 cellspacing=0>
<tr valign=top><td align=right class=tiny_text><b>Server</b>: <i><? echo $gGameServerName; ?></td><tr>
</table>
<a href="viewregion.php"  class=menuitem target=_new>View Regions</a>&nbsp;|&nbsp;
<a href="viewnation.php" class=menuitem target=_new>View Nations</a>&nbsp;|&nbsp;
</center>

<br>

<form name=regionid method=request action=join.php>	
<? if ($region == null) { ?>
	<table width=100%>
<? } else { ?>
	<table width=100%>
<? } ?>
<tr><td><center><font size=+1><b>Claim Your Region</b></font></center></td></tr>
<tr><td align=center><? echo $dropdowntxt; ?><input type=button onClick="javascript:DoToggle('joinmap',this);" value='<? echo $buttontxt; ?>' id='maptext'></td>
<tr><td align=center class=tiny_text>(Click on a highlighted region to see more information or to claim it)</td></tr>
</table>
</form>

<DIV ID="joinmap" <? echo $inivistr; ?>> 
<table width=100% height=60%>
<tr VALIGN=BOTTOM>
<td align=center height=100%>
<iframe src="worldmap.php?map=freeregions" width="100%" height="100%" valign=bottom align=center name="mapiframe" id="mapiframe"></iframe>
</td>
</tr>
</table>
</div>


<?

if ($region != null)
{

?>
<hr>
<center><font size=+1><b>Regional Information</b></font></center>
<table width=25%>
<tr><td>Map:</td><td><img border=1 src="<? echo "viewmap.php?action=viewregion&id=".$region->id; ?>" name=""></td></tr>
<tr><td>Region Name:</td><td><? echo $region->name; ?></td></tr>
<tr><td>Population:</td><td><? echo $region->population; ?></td></tr>
<tr><td colspan=2><a href="createnation.php" target="_top">Select this Region</a></td></tr>
</table>

</table>

<?	
}
?>
</html>
</body>