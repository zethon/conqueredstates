<?
//-----------------------------------------------------------------------------
// $RCSFile: viewissues.php $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/12 21:10:15 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./nations.php');
require_once('./regions.php');
require_once('./maps.php');

function getWorldNationImageMap1($nationlink)
{
	global $DBObj;
	
	$retval = "";
	$retval = "<map name='worldnations'>\n";

	// the nation objects
	$nationArray = array();
	$nloader = new NationLoader();
		
	$query = "select DISTINCT nation_id from nation_regions";
	$result = $DBObj->query($query);
	
	// create the array of nation objects
	while ($storeitem = $DBObj->fetch_array($result))
	{
		$nationid = $storeitem["nation_id"];
		$nation = $nloader->loadNation($nationid);
		
		if ($nation != null)
			array_push($nationArray,$nation);
	}	
	
	foreach ($nationArray as $nation)
	{
		foreach ($nation->regions as $region)
		{
			foreach ($region->coordsArray as $coords)
			{
				$text = "Nation: ".(htmlspecialchars($nation->name))."<br>";
				$text .= "Region: ".(htmlspecialchars($region->name))." (<a href=\'viewregion?regionid=".$region->id."\' target=_top>view</a>)";
				
				$temp = $nationlink;
				$temp = str_replace("%nationid%",$nation->id,$temp);
				$retval .=  "<area shape=poly coords=\"".@implode(",",$coords)."\" href=\"#\" onClick=\"javascript:doClickNation('$nation->id');\" onmouseover=\"doHoverTooltip(event,'$text')\" onmouseout=\"hideHoverTip()\" target=\"_top\">\n";
			}	
		}
	}	
	
	$retval .= "</map>\n";	
	return $retval;
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States: Nations Map (Server: <? echo $gGameServerName; ?>)</title>

<style type="text/css">
<!--
html,body 
{
	margin: 0;
	padding: 0;
}

-->
</style>


<link rel="stylesheet" href="/css/style.css" type="text/css">
<script src="/clientscript/tooltip.js" type="text/javascript"></script>

<script type="text/javascript">
<!--
function doClickNation(natid)
{
	window.opener.location = '<? echo $gMainURL; ?>/viewnation.php?nationid='+natid;
	return false;
}

function doClose()
{
	window.opener.mapWindow = null;
	return true;
}
-->
</script>

</head>

<body onLoad="Tooltip.init();" onUnload="doClose();">	

<?

	$start = microtime_float();
	$map = createWorldNationMap();
	$stop = microtime_float()-$start;
	echo "<!--Image Generation Time: [$stop]-->\n";
	
	$start = @microtime();
	$map = getWorldNationImageMap1("viewnation.php?nationid=%nationid%");
	$stop = @microtime()-$start;
	echo "<!--ImageMap Generation Time: [$stop]-->\n";
	
	echo "<img src='$gImagesURL/$gNationWorldMap' usemap='#worldnations' alt='World Map'>\n";
	echo $map;

?>

</body>
</html>