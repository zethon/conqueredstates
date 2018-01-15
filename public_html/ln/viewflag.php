<?
//-----------------------------------------------------------------------------
// $RCSFile: viewmap.php $ $Revision: 1.2 $ $Author: addy $ 
// $Date: 2006/06/09 22:24:03 $
//-----------------------------------------------------------------------------
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 

$flagid = $_REQUEST['id'];

// invalid flag ID
if (!is_numeric($flagid))
	exit;
	
$data = $DBObj->query_first("SELECT * FROM nation_flags WHERE (flagid = '$flagid')");

// flag ID not found
if (!is_numeric($data['flagid']))
	exit;


if ($data['type'] == "0")
{
	$filename = "$gImagesDir/flags/$data[name]";
	
	if (!file_exists($filename))
		exit;
		
	$instr = fopen($filename,"rb");
	$bytes = fread($instr,filesize($filename));

  header("Content-type: image/jpeg");
  print $bytes;
  exit;	
}
else
{
  header("Content-type: image/jpeg");
  print $data[imgdata];
  exit;	
	
}
?>