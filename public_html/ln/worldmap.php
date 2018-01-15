<?
//-----------------------------------------------------------------------------
// $RCSfile: worldmap.php,v $ $Revision: 1.13 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
// this is the file that gets included in the iframe. This is responsible for generating
// the world region and nation maps and the image maps
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./maps.php');

$map = $_REQUEST['map'];

if (strlen($map) == 0)
	exit;

@printPageHeader();	
	
if (strtolower($map) == 'regions')
{
	$start = microtime_float();
	@createWorldRegionMap("$gImagesDir/$gRegionWorldMap");
	$stop = microtime_float()-$start;

	echo "<!-- Image Generation Time: [$stop]-->\n";
	
	$start = @microtime();
	$map = getWorldRegionMapImageMap("viewregion.php?regionid=%id%");
	$stop = @microtime()-$start;
	
	echo "<!--ImageMap Generation Time: [$stop]-->\n";
	
	echo "<img src='$gImagesURL/$gRegionWorldMap' usemap='#worldregions'>\n";
	echo $map;
} // if (strtolower($map) == 'regions')

else

if (strtolower($map) == 'nations')
{
	$start = microtime_float();
	$map = createWorldNationMap();
	$stop = microtime_float()-$start;
	
	echo "<!--Image Generation Time: [$stop]-->\n";
	
	$start = @microtime();
	$map = getWorldNationImageMap("viewnation.php?nationid=%nationid%");
	$stop = @microtime()-$start;
	
	echo "<!--ImageMap Generation Time: [$stop]-->\n";
	
	echo "<img src='$gImagesURL/$gNationWorldMap' usemap='#worldnations'>\n";
	echo $map;
}
else

if (strtolower($map) == 'freeregions')
{
	$query = "SELECT regions.region_id, name, region_coords.coords,nation_regions.nation_id FROM regions LEFT JOIN region_coords USING (region_id) LEFT JOIN nation_regions USING (region_id)WHERE (region_coords.region_id > 0) AND (nation_id IS NULL) ORDER BY region_id";
	
	$start = microtime_float();
	createWorldRegionMap("$gImagesDir/$gFreeRegionWorldMap",true,$query); // refresh image every 60 minutes (will need to be changed)
	$stop = microtime_float()-$start;

	echo "<!-- Image Generation Time: [$stop]-->\n";
	
	$start = @microtime();
	$map = getWorldRegionMapImageMap("join.php?regionid=%id%",$query);
	$stop = @microtime()-$start;
	
	echo "<!--ImageMap Generation Time: [$stop]-->\n";
	
	echo "<img src='$gImagesURL/$gFreeRegionWorldMap' usemap='#worldregions'>\n";
	echo $map;	
}

printPageFooter();

// ************* FUNCTIONS *********************
function printPageHeader()
{
?>
<html>

<style>
<!--
html,body 
{
	margin: 0;
	padding: 0;
}

-->
</style>

<link rel="stylesheet" href="css/style.css" type="text/css">
<script src="clientscript/tooltip.js" type="text/javascript"></script>

<body onLoad="Tooltip.init();">	
<?	
}

function printPageFooter()
{
?>
</body>
</html>
<?
}

?>

