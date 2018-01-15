<?
//-----------------------------------------------------------------------------
// $RCSfile: delnation.php,v $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./regions.php');
require_once('./nations.php');
require_once('./users.php');
require_once('./maps.php');

if ($_REQUEST['id'] != "" && intval($_REQUEST['id']))
{
	$nwriter = new NationWriter();
	if ($nwriter->deleteNation($_REQUEST['id']))
	{
		$query = "SELECT regions.region_id, name, region_coords.coords,nation_regions.nation_id FROM regions LEFT JOIN region_coords USING (region_id) LEFT JOIN nation_regions USING (region_id)WHERE (region_coords.region_id > 0) AND (nation_id IS NULL) ORDER BY region_id";
	
		$start = microtime_float();
		createWorldRegionMap("$gImagesDir/$gFreeRegionWorldMap",true,$query); // refresh image every 60 minutes (will need to be changed)
		$stop = microtime_float()-$start;

		echo "<b>Image Generation Time: [$stop]</b><br>";		
		echo "<h1>NATION DELETE</h1>";
	}
	else
	{
		echo "<h1>COULD NOT NATION DELETE</h1>";
	}
}
?>
HI!