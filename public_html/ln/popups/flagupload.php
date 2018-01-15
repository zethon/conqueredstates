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
require_once('./users.php');

$userloader = new UserLoader();
$gLobbyUser = $gUser = $userloader->loadCookieUser();
$nation = null;

if ($gUser != null)
{
	$nloader = new NationLoader();
	$nation = $nloader->loadNationWithStats($gUser->countryid,false);
}

if ($nation==null || $gUser == null)
{
	WriteLog("WARNING:unathorized attemp to view settings");
	header("Location: $gMainURL");
}


define('MAX_IMAGE_WIDTH',200);
define('MAX_IMAGE_HEIGHT',100);
define('MAX_FILE_SIZE',(50*1024)); // bytes

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States : Custom Flag Upload</title>

<link rel="stylesheet" href="/css/style.css" type="text/css">
<link rel="stylesheet" href="/css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script type="text/javascript">
<!--
function doSubmit()
{
	if (!DHTML) return;
	
	obj = document.flagfileform;
	if (obj.flagfile.value == "")
	{
		alert ("Please select a file to upload!");
		return false;
	}
	else
	{
		var x = new getObj('filestatus');	
		x.style.display = '';
		obj.submit();	
	}
}

function doClose()
{
	window.opener.location = "<? echo $gMainURL; ?>/viewsettings.php";
	window.close();
}
-->
</script>
</head>

<body>
<p>Use the "Browse" button to find the file you want to upload as your flag. Images must be smaller than <? echo MAX_IMAGE_WIDTH."x".MAX_IMAGE_HEIGHT; ?> and may not 
exceed <? echo floor(MAX_FILE_SIZE/1024); ?>k.</p>

<p><b>Only images in the JPG format will be accepted!</b></p>

<form enctype=multipart/form-data method=post action=flagupload.php name=flagfileform>
<input type=hidden name=formaction value=upload>
<input type="file" name="flagfile">&nbsp;&nbsp;<input type="button" value="Upload" onClick="doSubmit();">
</form>

<?
	if ($_REQUEST['formaction'] == 'upload')
	{
		$dosave = false;
		$filename = "";
		$remotefile = $_FILES['flagfile']['name'];
		$fileext = (strpos($remotefile,'.')===false?'':substr(strrchr($remotefile, "."), 1));

		if (strtolower($fileext) != "jpg" && strtolower($fileext) != "jpeg")
		{
			print "<b class=error_text>Invalid file extension. Only jpg and jpeg are valid extensions.</b>";
		}
		else
		{
				print ("<hr><p><b>Uploaded: $remotefile ($fileext)</b></p>");
				$filename = $gImagesDir."/temp/".time().".img";
				$isgood = move_uploaded_file($_FILES['flagfile']['tmp_name'],$filename);
							
				if (filesize($filename) > MAX_FILE_SIZE)
				{
					print "<b class=error_text>File too big. Maximum size is ".(floor(MAX_FILE_SIZE/1024))."k</b>";
				}
				else
				{
					$img = @imagecreatefromjpeg($filename);
					if ($img == null)
					{
						print "<b class=error_text>Invalid or corrupt JPEG image</b>";	
					}
					else if (imagesx($img) > MAX_IMAGE_WIDTH)
					{
						print "<b class=error_text>The image is too wide. The maximum width is ".MAX_IMAGE_WIDTH." pixels.</b>";	
					}
					else if (imagesy($img) > MAX_IMAGE_HEIGHT)
					{
						print "<b class=error_text>The image is too tall. The maximum height is ".MAX_IMAGE_HEIGHT." pixels.</b>";	
					}
					else // looks valid
					{
						@imagedestroy($img); 
						$img = null;
						
						$data = $DBObj->query_first("SELECT type FROM nation_flags WHERE (flagid = '$nation->flagid')");
						
						if (isset($data['type']))
						{ 
							$instr = fopen($filename,"rb"); // read the file data
							$image = addslashes(fread($instr,filesize($filename)));
							
							if ($data['type'] == "0") // using an image file, use INSERT
							{
								$DBObj->query("INSERT INTO nation_flags (type,imgdata)  VALUES ('1','$image')");
								
								$newid = $DBObj->insert_id();
								$DBObj->query("UPDATE nation SET flag='$newid' WHERE (nation_id = '$nation->id')");
							}
							else if ($data['type'] == "1")// image already stored in DB, use update
							{
								$DBObj->query("UPDATE nation_flags SET imgdata='$image' WHERE (flagid = '$nation->flagid')");
							}
							
							print "<br><b>File upload successfully!</b>\n";
							print ("<script>doClose();</script>");
						}
					}
					
					if ($img != null)
						@imagedestroy($img);
				}
		}		

		if (file_exists($filename))
			unlink($filename);
	}
?>

<DIV ID="filestatus" style="display:none"> 
<table width=100%>
<tr><td><hr></td></tr>
<tr><td><b>Uploading file...</b></td></tr>
</table>
</DIV>

</body>
</html>