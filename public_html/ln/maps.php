<?
//-----------------------------------------------------------------------------
// $RCSfile: maps.php,v $ $Revision: 1.19 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php');
require_once('./nations.php');

function createWorldRegionMap($outfile,$force=false,$query="SELECT regions.region_id, name, population, region_coords.coords FROM regions LEFT JOIN region_coords USING (region_id) WHERE (region_coords.region_id > 0) ORDER BY region_id")
{
	global $DBObj;
	//global $dbname,$dbuser,$dbpw,$dbhost;
	global $gColorArray,$gBlankWorldMap,$gRegionWorldMap,$gImagesDir;
	global $gImagesDir,$gRegionWorldMap,$gRefreshMapAgeLimit;

	if (file_exists($outfile))
	{
		$filetime = filectime("$outfile");
		$ageinminutes = (((time() - $filetime)/60));
		if ($ageinminutes < $gRefreshMapAgeLimit && file_exists("$outfile"))
			if (!$force)
				return;	
	}

	$image = @imagecreatefromgif("$gImagesDir/$gBlankWorldMap");
	if (!$image)
	{
		echo "<h1>ERROR</h1>Couldn't load image file [$gImagesDir/$gBlankWorldMap]";
		return;
	}
	
	fillColorArray($image);
	$outline = imagecolorallocate($image,255,255,255);
		
	$curregion = 0; $coloridx = 0;
	$result = $DBObj->query($query);
	while ($storeitem = $DBObj->fetch_array($result))
	{
		$id = $storeitem["region_id"];
		$coords = $storeitem["coords"];
		
		if ($curregion != $id)
		{
			$curregion = $id;
			$coloridx++; if ($coloridx >= count($gColorArray)) $coloridx = 0;
		}
		
		$values = explode(",",$coords);
		//imagefilledpolygon($image, $values, floor(count($values)/2), $gColorArray[$coloridx]);     
		imagepolygon($image,$values,floor(count($values)/2),$outline);				
	}
	
	if (@file_exists("$outfile"))
		unlink("$outfile");
	
	@imagejpeg($image,"$outfile",40);
	@imagedestroy($image);		
}


function getWorldRegionMapImageMap($regionlink,$query="SELECT regions.region_id, name, population, region_coords.coords FROM regions LEFT JOIN region_coords USING (region_id) WHERE (region_coords.region_id > 0) ORDER BY region_id")
{
	global $DBObj;

	$result = $DBObj->query($query);

	$retval = "<map name='worldregions'>\n";
	while ($storeitem = $DBObj->fetch_array($result))
	{
		$id = $storeitem["region_id"];
		$name = $storeitem["name"];
		$population = $storeitem["population"];
		$coords = $storeitem["coords"];

		$temp = $regionlink;
		$temp = str_replace("%id%",$id,$temp);
		$temp = str_replace("%name%",$name,$temp);
		$temp = str_replace("%population%",$population,$temp);
		$temp = str_replace("%coords%",$coords,$temp);
		$retval .=  "<area shape=polygon coords=\"$coords\" href=\"$temp\" onmouseover=\"doTooltip(event,'$name')\" onmouseout=\"hideTip()\" target=\"_top\">\n";
		
		$i++;
	}
	
	$retval .= "</map>\n";	
	return $retval;		
}

function getWorldNationImageMap($nationlink)
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
				$text = "Nation: ".$nation->name."</br>";
				$text .= "Region: ".$region->name." (<a href=\'viewregion?regionid=".$region->id."\' target=_top>view</a>)";
				
				$temp = $nationlink;
				$temp = str_replace("%nationid%",$nation->id,$temp);
				$retval .=  "<area shape=polygon coords=\"".@implode(",",$coords)."\" href=\"$temp\" onmouseover=\"doHoverTooltip(event,'$text')\" onmouseout=\"hideHoverTip()\" target=\"_top\">\n";
			}	
		}
	}	
	
	$retval .= "</map>\n";	
	return $retval;
}

function createWorldNationMap($force=false)
{
	global $DBObj;
	global $gColorArray,$gBlankWorldMap,$gRegionWorldMap,$gImagesDir;
	global $gImagesDir,$gNationWorldMap,$gRefreshMapAgeLimit;

	// only check the filetime if the file exists
	if (file_exists("$gImagesDir/$gNationWorldMap"))
	{
		$filetime = filectime("$gImagesDir/$gNationWorldMap");
		$ageinminutes = (((time() - $filetime)/60));
			
		// no refresh needed so leave
		if ($ageinminutes < $gRefreshMapAgeLimit && file_exists("$gImagesDir/$gNationWorldMap"))
			if (!$force)
				return;	
	}
			
	// create the image
	
	// the nation objects
	$nationArray = array();
	$nloader = new NationLoader();
		
	// mysql
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
	
	// load up the blank world image
	$tempimage = @imagecreatefromgif("$gImagesDir/$gBlankWorldMap");
	if (!$tempimage)
	{
		echo "<h1>ERROR</h1>Couldn't load image file [$gImagesDir/$gBlankWorldMap]";
		return;
	}
	
	// now create the true color image and copy the blank world map onto it
	$image = imagecreatetruecolor(@imagesx($tempimage),@imagesy($tempimage));
	imagecopy($image,$tempimage,0,0,0,0,@imagesx($tempimage),@imagesy($tempimage));	
	@imagedestroy($tempimage);

	fillColorArray($image);
	//$outline = imagecolorallocate($image,255,255,255);
	
	// go through the nations
	$coloridx = 0;
	foreach ($nationArray as $nation)
	{
		foreach ($nation->regions as $region)
		{
			foreach ($region->coordsArray as $coords)
			{
				imagefilledpolygon($image, $coords, floor(count($coords)/2), $gColorArray[$coloridx]);   
				//imagepolygon($image,$coords,floor(count($coords)/2),$outline);
			}	
		}
		$coloridx++; if ($coloridx >= count($gColorArray)) $coloridx = 0;
	}
	
	// create the thumbnail image
	$thumb = @imagecreatetruecolor(550,219); // 550 x 219
	if (@imagecopyresized($thumb,$image,0,0,0,0,550,219,@imagesx($image),@imagesy($image)))
	{
		if (file_exists("$gImagesDir/small-$gNationWorldMap"))
				unlink("$gImagesDir/small-$gNationWorldMap");
				
		if (!@imagejpeg($thumb,"$gImagesDir/small-$gNationWorldMap",90))
		{
			print ("<H1>COULD NOT WRITE THE NATION MAP THUMBNAIL</h1>");
		}
	}
	@imagedestroy($thumb);
	
	// create the nation ma
	if (@file_exists("$gImagesDir/$gNationWorldMap"))
		unlink("$gImagesDir/$gNationWorldMap");
		
	if (!@imagejpeg($image,"$gImagesDir/$gNationWorldMap",50))
	{
		print ("<h1>COULD NOT WRITE THE NATION MAP!!</h1>");
	}
	
	
	//@imagejpeg($image,"$gImagesDir/lowres-$gNationWorldMap",5);
	@imagedestroy($image);
}

function get_zoom_map($values)
{
	$left = $right = $top = $bottom = -1;
	
	//$x = $y = $h = $w = 0;
	
	$i = 1;
	foreach ($values as $val)
	{
		// y coordinate
		if (($i % 2) == 0)
		{
			if ($top == -1)
				$top = $val;
				
			if ($val > $bottom)
			{
				$bottom = $val;
			}
			else if ($val < $top)
				$top = $val;			
		}
		else // x coordinate
		{
			if ($left == -1)
				$left = $val;
			
			if ($val > $right)
				$right = $val;
			else if ($val < $left)
				$left = $val;
		}
		
		$i++;
	}

	$left -= 25; if ($left < 0) $left = 0;
	$top -= 25; if ($top < 0) $top = 0;
	$bottom += 25;
	$right += 25;

	return array($left, $top, $right-$left, $bottom-$top);
}

return 1;
?>