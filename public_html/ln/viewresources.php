<?
//-----------------------------------------------------------------------------
// $RCSFile: viewissues.php $ $Revision: 1.1 $ $Author: addy $ 
// $Date: 2006/07/14 02:52:30 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
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
	$nation = $nloader->loadNationWithStats($gUser->countryid);
}

if ($nation==null || $gUser == null)
{
	WriteLog("WARNING:unathorized attemp at viewissues.php");
	header("Location: $gMainURL");
	exit;
}

// build the resource array
$resArray = array();
$datatable = $DBObj->query("SELECT * FROM resources");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$resobj = new Resource();
	$resobj->resource_id = $storeitem['resource_id'];
	$resobj->name = $storeitem['resource_name'];
	$resobj->unit = $storeitem['resource_unit'];
	$resobj->producername = $storeitem['producer_name'];
	$resArray[$storeitem['resource_id']] = $resobj;
}

$resourceConCalc = new ResConsumptionCalc();

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States</title>

<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>


</head>

<body>
<!-- <? echo "[$gAction]"; ?>-->

<!-- BANNER CELL -->
<table class=highlight width=100% cellpadding=1 cellspacing=1>
<tr valign=top><td>
<table width=100% align=center cellspacing=0 cellpadding=0 bgcolor=#DEE7F7>
<tr><td colspan=4 align=center>
<? include "./includes/banner.inc"; ?>
</td></tr>
</table>
</td></tr>
</table>
<!-- /BANNER CELL -->

<br>

<table width=100% align=center style="height:75%">
<tr valign=top>
<!-- LEFT SIDE MENU -->
<td width=109 valign=top>
  <? include "./includes/sidemenu.inc"; ?>
</td>
<!-- /LEFT SIDE MENU -->

<!-- MAIN CONTENT -->
<td valign=top>

<? 
	foreach ($resArray as $resobj)
	{
		$nresource = $nation->resources[$resobj->resource_id];
?>
<table width=800 class="border_table2">
<tr><td colspan="2" class="highlight"><? echo ucfirst($resobj->name); ?> </td></tr>
<tr><td colspan="2"><b>Reserves:</b> <? echo number_format($nresource->quantity)." $resobj->unit"."s"; ?> </td></tr>
<? 

		// calc and print consumption
		$resourceConCalc->setProp('nation',$nation);
		$yearly = ceil($resourceConCalc->CalculateConsumption($nresource->resource_id));		
		$daily = ceil($yearly/365);	

		print ("<tr class=label><td colspan=4><b>Consumption:</b></td></tr>\n");
		print ("<tr class=label><td>".(number_format($yearly))." $resobj->unit"."s per year&nbsp;|&nbsp;".(number_format($daily))." $resobj->unit"."s per day</td></tr>\n"); 

		// calc and print production
		$totalproduced = 0;
		foreach ($nation->regions as $region)
		{
			$rresource = $region->resources[$nresource->resource_id];
			$totalproduced += ($rresource->productionrate * $rresource->producercount);
		}		

		print ("<tr class=label><td colspan=4><b>Production:</b></td></tr>\n");
		print ("<tr class=label><td>".(number_format($totalproduced*365))." $resobj->unit"."s per year&nbsp;|&nbsp;".(number_format($totalproduced))." $resobj->unit"."s per day</td></tr>\n"); 
		
		print ("<tr><td colspan=2>\n");

		print ("<table>\n");
		
		foreach ($nation->regions as $region)
		{
			$resource = $region->resources[$resobj->resource_id];
			print "			

<tr class=row_title><td colspan=2>Region: $region->name</td></tr>
<tr><td>Number of Producers (Max: $resource->producerlimit):</td><td><input style='width:50px' value='$resource->producercount'></td></tr>
		
			";
			
		}
		print ("</table>\n");
		print ("</td></tr>\n");
?>



</table>
<br>

<?
	}
?>




</td>
<!-- /MAIN CONTENT -->
</tr>
</table>

</body>
</html>