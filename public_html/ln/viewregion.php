<?
//-----------------------------------------------------------------------------
// $RCSfile: viewregion.php,v $ $Revision: 1.15 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
//require_once('./global.php');
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./maps.php');
require_once('./users.php');

$userloader = new UserLoader();
$gUser = $userloader->loadCookieUser();

$regionid = $_REQUEST['regionid'];

if (intval($regionid) && $regionid != "")
{
	$regloader = new RegionLoader();
	$region = $regloader->loadRegion($regionid);
	
	if ($region != null)
	{
		$string = "";
		foreach ($region->coordsArray as $coords)
		{
			if (strlen($string) > 0)
				$string .= ",";
			
			$string .= @implode(",",$coords);
		}		
		list($src_x,$src_y,$src_w,$src_h) = get_zoom_map(explode(",",$string));		
	}	
}	

// build the regional download dropdown
$datatable = $DBObj->query("SELECT DISTINCT regions.region_id,name FROM regions LEFT JOIN region_coords USING (region_id) WHERE (region_coords.region_id IS NOT NULL) ORDER BY regions.name");
if ($DBObj->num_rows($datatable) > 0)
{
	$regdropdowntxt = "<select name='regionid' onChange='javascript:submit()'>\n";

	$i = 0;
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$id = $storeitem["region_id"];
		$name = $storeitem["name"];			
		
		$selected = "";
		if ($regionid == $id)
			$selected = "selected=true";
		
		$regdropdowntxt .= "<option $selected value='$id'>$name</option>\n";				
		
		$i++;			
	}
	$regdropdowntxt .= "</select>\n";			
}

if ($CSCollapsed['regionmap'])
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


<link rel="stylesheet" href="css/main.css" type="text/css">

<body onLoad="doScroll(<? echo floor($src_x+($src_w/2)); ?>,<? echo floor($src_y+($src_w/2)); ?>,'mapiframe');">
<center>
<hr width=800><font class=banner_text>Conquered States</font><hr width=800>
<? if ($gUser == null) { ?>
	<a href="<? echo "$gLobbyLogin"; ?>"  class=menuitem>Login</a>&nbsp;|&nbsp;
	<a href="<? echo "$gLobbyRegister"; ?>" class=menuitem>Register</a>&nbsp;|&nbsp;
<? } ?>
<a href="index.php" class=menuitem>Home</a>

<? if ($gUser != null) { ?>
	&nbsp;|&nbsp;<td><a href="<? echo "$gLobbyLogout"; ?>" class=menuitem>Logout</a></td>
<? } ?>
</center>

<br><br>

<form name=regionid method=request action=viewregion.php>
<table width=100% cellpadding=0 cellspacing=0>
<tr><td colspan=2 align=center><font size=+1><b>World Regional Map</b></font></td></tr>
<tr><td>&nbsp;</td></tr>
<tr><td align=center><? echo $regdropdowntxt; ?><input type=button onClick="javascript:DoToggle('regionmap',this);" value='<? echo $buttontxt; ?>' id='maptext'></td></tr>
</table>
</form>

<DIV ID="regionmap" <? echo $inivistr; ?>> 
<table width=100% height=60%>
<tr VALIGN=BOTTOM>
<td align=center height=100%>
<iframe src="worldmap.php?map=regions" width="95%" height="100%" valign=bottom align=center name="mapiframe" id="mapiframe"></iframe>
</td>
</tr>
</table>
</DIV> 

<?
	if ($region != null)
	{
?>
<hr>
<center><font size=+1><b>Regional Information</b></font></center>
<table width=25%>
<!--<tr><td>Map:</td><td><img border=1 src="<? echo $imgurl ?>" name=""></td></tr>-->
<tr><td>Map:</td><td><img border=1 src="<? echo "viewmap.php?action=viewregion&id=".$region->id; ?>" name=""></td></tr>
<tr><td>Region Name:</td><td><? echo $region->name; ?></td></tr>
<tr><td>Population:</td><td><? echo $region->population; ?></td></tr>
</table>
<?
	}
?>


</body>
</html>	