<?
//-----------------------------------------------------------------------------
// $Workfile: query.php $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/05/18 00:40:55 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');

if ($_REQUEST['key'] != $gGameKey)
	exit;
	
$result = $DBObj->query("SELECT DISTINCT regions.region_id FROM regions LEFT JOIN nation_regions USING (region_id) WHERE (nation_id IS NULL)");
$unclaimedregions = $DBObj->num_rows($result);

$result = $DBObj->query("SELECT DISTINCT nation.nation_id FROM nation LEFT JOIN nation_regions USING (nation_id) WHERE (nation_region_id IS NOT NULL)");
$nationcount = $DBObj->num_rows($result);

header('Content-Type: text/xml');
echo "<game>";
echo "<freeregions>$unclaimedregions</freeregions>";
echo "<nationcount>$nationcount</nationcount>";
echo "</game>";

?>