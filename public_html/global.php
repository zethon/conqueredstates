<?php
//-----------------------------------------------------------------------------
// $Workfile: index.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/05/23 21:36:46 $
//-----------------------------------------------------------------------------

$DEBUG = 1;

// database settings
$dbhost = "localhost";
$dbuser="quyulvfc_lobbyus";
$dbpw="";
$dbname="quyulvfc_lobby";

// web info
$gURLDomain = ".conqueredstates.com";

function TRACE($str)
{
	global $DEBUG;
	
	if ($DEBUG)
		echo $str."\n";
}

function SafeQueryData($query)
{
	$query = preg_replace( "/'/", "\\'", $query );		
	return $query;
}

function isValidUser($username,$password)
{
	global $dbhost,$dbuser,$dbpw,$dbname;

	if (!@mysql_connect($dbhost,$dbuser,$dbpw))
		return 0;
	
	if (!@mysql_select_db($dbname))
		return 0;

	$query = "SELECT * FROM users WHERE (username = '$username')";
	$result = mysql_query("SELECT * FROM users WHERE (username = '".$username."')");
	$num = mysql_numrows($result);	

	if ($num != 1)
		return 0;
	
	$storedpw = @mysql_result($result,0,"password");

	if ($storedpw == $password)
		return true;

	return 0;
}


?>