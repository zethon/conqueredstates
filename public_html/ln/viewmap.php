<?
//-----------------------------------------------------------------------------
// $RCSfile: viewmap.php,v $ $Revision: 1.8 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php');
require_once('./nations.php');
require_once('./maps.php');

if ($_REQUEST['action'] != "viewregion" && $_REQUEST['action'] != "viewnation")
	exit;
	
if ($_REQUEST['id'] < 1)
	exit;
	
$id = $_REQUEST['id'];
	
if ($_REQUEST['action'] == "viewnation")
{
	$loader = new NationLoader();
	$nation = $loader->loadNation($id);
	
	if ($nation == null)
	{
		$image = @imagecreatefromgif("$gImagesDir/$gBrokenImage");
		header("Content-Type: image/gif");
		@imagegif($image);
		@imagedestroy($image);		
		exit;
	}
	
	// load the world image
	$image = @imagecreatefromgif("$gImagesDir/$gBlankWorldMap");
	if ($image == null)
		exit;
	
	@fillColorArray($image);		
	$color = imagecolorallocate($image,255,255,0);
	
	$string = "";
	foreach ($nation->regions as $region)
	{
		foreach ($region->coordsArray as $coords)
		{
			if (strlen($string) > 0)
				$string .= ",";
				
			$string .= @implode(",",$coords);
			
			@imagepolygon($image, $coords, floor(count($coords)/2), $color);  
			//@imagefilledpolygon($image, $coords, floor(count($coords)/2), $gColorArray[0]);   
		}
	}	
	
	list($src_x,$src_y,$src_w,$src_h) = get_zoom_map(explode(",",$string));
	$nationimage = imagecreatetruecolor($src_w, $src_h);	
	@imagecopy($nationimage,$image,0,0,$src_x,$src_y,$src_w,$src_h);
	//@imagecopyresized($nationimage,$image,0,0,$src_x,$src_y,$src_w,$src_h);
	
	header("Content-Type: image/gif");
	@imagegif($nationimage);
	@imagedestroy($nationimage);
	@imagedestroy($image);
}
else if ($_REQUEST['action'] == "viewregion")
{
	global $gBrokenImage,$gColorArray,$gBlankWorldMap,$gImagesURL,$gImagesDir;
	global $gRegionWorldMap,$gRefreshMapAgeLimit;

	// retreive the region
	$regloader = new RegionLoader();
	$region = $regloader->loadRegion($id);
	
	if ($region == null)
	{
		$image = @imagecreatefromgif("$gImagesDir/$gBrokenImage");
		header("Content-Type: image/gif");
		@imagegif($image);
		@imagedestroy($image);		
		exit;
	}

	// load the world image
	$image = @imagecreatefromgif("$gImagesDir/$gBlankWorldMap");
	if ($image == null)
		return false;

	$color = imagecolorallocate($image,0,0,0);
	fillColorArray($image);
	
	$string = "";
	foreach ($region->coordsArray as $coords)
	{
		if (strlen($string) > 0)
			$string .= ",";
		
		$string .= @implode(",",$coords);
		imagefilledpolygon($image, $coords, floor(count($coords)/2), $gColorArray[0]);     
		imagepolygon($image,$coords,floor(count($coords)/2),$color);			
	}		

	list($src_x,$src_y,$src_w,$src_h) = get_zoom_map(explode(",",$string));
	$regionimage = imagecreatetruecolor($src_w, $src_h);		
	@imagecopy($regionimage,$image,0,0,$src_x,$src_y,$src_w,$src_h);
	//@imagecopyresized($regionimage,$image,0,0,$src_x,$src_y,$src_w,$src_h);
	
	header("Content-Type: image/gif");
	@imagegif($regionimage);
	@imagedestroy($image);
	@imagedestroy($regionimage);
}
?>
