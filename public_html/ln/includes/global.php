<?php
//-----------------------------------------------------------------------------
// $Workfile: global.php $ $Revision: 1.8 $ $Author: addy $ 
// $Date: 2006/06/19 18:27:46 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~ E_WARNING);
//$DEBUG = 1;


// Game Database Settings
$GameDB_Server = "localhost";
$GameDB_User = "quyulvfc_lobbyus";
$GameDB_Pass = "";
$GameDB_Name = "quyulvfc_ln";

// LOBBY SETTINGS
$LobbyDB_Server = "localhost";
$LobbyDB_User="quyulvfc_lobbyus";
$LobbyDB_Pass="";
$LobbyDB_Name="quyulvfc_lobby";

$gMainURL = "http://ln.conqueredstates.com";
$gHomeDir = "/home/quyulvfc/public_html/ln";

$gLobbyURL = "http://www.conqueredstates.com";
$gLobbyListGames = "$gLobbyURL/index.php?action=listgames";
$gLobbyLogin = "$gLobbyURL/index.php?action=ls";
$gLobbyLogout = "$gLobbyURL/index.php?action=logout";
$gLobbyRegister = "$gLobbyURL/index.php?action=register";


// lobby key
$gGameKey = '123456789';
$gGameServerName = 'ln';

// cookie setttings
$gCookieLogin = "e2k69_username";
$gCookiePassword = "e2k69_id";

//// creating a nation cookies
$gJoiningCountry = "e2k69_newregion";

// webinfo
$gImagesURL = "$gMainURL/images";

//path info
$gImagesDir = "$gHomeDir/images";

// files
$gBlankWorldMap = "large-blankworldmap.gif";
$gRegionWorldMap = "regional-worldmap.jpg"; 
$gNationWorldMap = "national-worldmap.jpg";
$gFreeRegionWorldMap = "freeregion-worldmap.jpg";
$gBrokenImage = "404.gif";

// map refresh age (in minutes)
$gRefreshMapAgeLimit = 60;

$gColorArray = array();

function fillColorArray($image)
{
	global $gColorArray;
	$gColorArray = array();
	
	$alpha = 20;

	array_push($gColorArray,imagecolorallocatealpha($image, 0xFF,0xFF,0x80,$alpha)); // yellow
	array_push($gColorArray,imagecolorallocatealpha($image, 0xC0,0xC0,0xC0,$alpha)); // grey
	//array_push($gColorArray,imagecolorallocatealpha($image, 0x6a,0x5a,0xcd,$alpha)); // slate blue
	array_push($gColorArray,imagecolorallocatealpha($image, 0xd8,0xbf,0xd8,$alpha)); // thistle		
	
//	array_push($gColorArray,imagecolorallocatealpha($image, 127,191,85,$alpha));		
//	array_push($gColorArray,imagecolorallocatealpha($image, 255,251,240,$alpha));	
//	array_push($gColorArray,imagecolorallocatealpha($image, 0,153,0,$alpha)); // dark green
//	array_push($gColorArray,imagecolorallocatealpha($image, 0xAC,0xB5,0xC5,$alpha));
//	array_push($gColorArray,imagecolorallocatealpha($image, 0xDE,0xE7,0xF7,$alpha));
//	array_push($gColorArray,imagecolorallocatealpha($image, 0xd0,0xd0,0x40,$alpha));

	
}


function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function isValidLobbyUser($username,$password)
{
	global $LobbyDB_Server,$LobbyDB_User,$LobbyDB_Pass,$LobbyDB_Name;
	global $GameDB_Server,$GameDB_User,$GameDB_Pass,$GameDB_Name;
	
	$LobbyDBObj = new DB_Sql_vb;
	$LobbyDBObj->database = $LobbyDB_Name;
	
	if (!$LobbyDBObj->connect($LobbyDB_Server, $LobbyDB_User, $LobbyDB_Pass,0))
		return false;

	$data = $LobbyDBObj->query_first("SELECT * FROM users WHERE (username = '$username')");

	// we need to reset the mysql_connect() command :rolls:
	$LobbyDBObj = new DB_Sql_vb;
	$LobbyDBObj->database = $GameDB_Name;
	$LobbyDBObj->connect($GameDB_Server, $GameDB_User, $GameDB_Pass,0);
	
	return ($password == $data["password"]);
}

function SafeQueryData($query)
{
	$query = preg_replace( "/'/", "\\'", $query );		
	return $query;
}

// we're going to display the screen, so setup the functions
function printTitleDropDown1($selid = -1)
{
	$DBObj = $GLOBALS['DBObj'];	
		
	$query = "SELECT * FROM nation_titles ORDER BY nation_title_text";
	$datatable = $DBObj->query($query);
	
	echo "<select name='title'>\n";
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$selected = "";
		$title = $storeitem["nation_title_text"];
		$id = $storeitem["nation_title_id"];
		
		if ($selid == $id)
			$selected = "selected=true";
			
		echo "<option $selected value=$id>$title</option>\n";
		
	}
	echo "</select>\n";
}


// we're going to display the screen, so setup the functions
function printCurrencySymbolDropDown($selid = -1, $name = 'currency_symbol')
{
	$DBObj = $GLOBALS['DBObj'];	
		
	$query = "SELECT * FROM currency_symbols ORDER BY currency_id";
	$datatable = $DBObj->query($query);
	
	echo "<select name='$name'>\n";
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$selected = "";
		$title = $storeitem["currency_symbol"];
		$id = $storeitem["currency_id"];
		
		if ($selid == $id)
			$selected = "selected=true";
			
		echo "<option $selected value=$id>$title</option>\n";
		
	}
	echo "</select>\n";
}

function printFlagDropDown($selid = -1, $inputname = "flag", $onchange = null, $printdefault = false)
{
	$DBObj = $GLOBALS['DBObj'];	
	$datatable = $DBObj->query("SELECT flagid,name,title FROM nation_flags WHERE (type = '0') ORDER BY title");
		
	print "<select name='$inputname' $onchange>\n";
	
	if ($printdefault == true)
	{
		print "<option value='-1'>Custom</option>\n";
	}
		
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$selected = "";
		if ($selid == $storeitem['flagid'])
			$selected = "selected=true";
					
		print "<option $selected value='$storeitem[flagid];$storeitem[name]'>$storeitem[title]</option>\n";
	}		
	print "</select>\n";
}


function WriteLog($event,$user="")
{
	global $gHomeDir;
	
	if ($user == "")
	{
		$user = "ANONYMOUS";
	}

	$time = time();
	$month = date("m");
	$day = date("d");
	$year = date("Y");
	$filename = "$year-$month-$day.log";
	
	$fp = fopen("$gHomeDir/logs/$filename","a");
	$result = fwrite($fp,date("H:i:s",time()).":$user:$_SERVER[REMOTE_ADDR]:$event\n");
	fclose($fp);  		
}


return;
?>

