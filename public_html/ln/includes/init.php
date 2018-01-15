<?
//-----------------------------------------------------------------------------
// $Workfile: init.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/06/02 21:33:02 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/db_mysql.php');
require_once('./includes/global.php');

// create the Database object
$DBObj = new DB_Sql_vb;
$DBObj->database = $GameDB_Name;
if (!$DBObj->connect($GameDB_Server, $GameDB_User, $GameDB_Pass,0))
{
	echo "<b>Cannot connect to database</b>";
	exit;
}

// create the collapsed cookie array
$CSCollapsed = array();
$_val = preg_split('#\n#', $_COOKIE['cs_collapse'], -1, PREG_SPLIT_NO_EMPTY);
foreach ($_val AS $_key)
{
	$CSCollapsed["$_key"] = 1;
}

return 1;
?>