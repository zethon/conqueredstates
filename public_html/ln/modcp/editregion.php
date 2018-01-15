<?
//-----------------------------------------------------------------------------
// $Workfile: editregion.php $ $Revision: 1.6 $ $Author: addy $ 
// $Date: 2006/05/26 18:55:26 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNINGS);

chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php'); 
require_once('./resources.php'); 
require_once('./nations.php'); 

$natLoader = new NationLoader();
$regLoader = new RegionLoader();

$gAction = $_REQUEST['formaction'];
$gStatus = "";

// *** PROCESS ACTIONS
if ($gAction == 'savereg')
{
	$regid = @SafeQueryData($_REQUEST['regionid']);
	$regpop = @SafeQueryData($_REQUEST['regionpopulation']);
	$regname = @SafeQueryData($_REQUEST['regionname']);
	
	$DBObj->query("UPDATE regions SET name='$regname',population='$regpop' WHERE (region_id = '$regid')");
	
	if ($DBObj->affected_rows() >= 1)
		$gStatus = "Data saved successfully";
	else
		$gStatus = "Unable to save data";
}
else if ($gAction == 'savecoords' || $gAction == 'delcoords')
{
	$regionid = @SafeQueryData($_REQUEST['region_id']);
	$region = $regLoader->loadRegion($regionid);
	
	if ($region != null)
	{
		$idx = 0;
		foreach ($region->coordsArray as $coord)
		{
			$coordid = $region->coordsIDArray[$idx];
			$coordstr = implode(",",$coord);			
			
			$formcoordchk = @SafeQueryData($_REQUEST["coords_check_$coordid"]);
			$formcoords = @SafeQueryData($_REQUEST["coords_$coordid"]);
			
			if ($formcoordchk == 'on' && $coordstr != $formcoords && $gAction == 'savecoords')
			{
	 			$DBObj->query("UPDATE region_coords SET coords='$formcoords' WHERE (region_coords_id = '$coordid')");

				
				if ($DBObj->affected_rows() >= 1)
					$gStatus = "Data saved successfully";
				else
					$gStatus = "Unable to save data";					
			} 
			else if ($formcoordchk == 'on' && $gAction == 'delcoords')
			{
				$DBObj->query("DELETE FROM region_coords WHERE (region_coords_id = '$coordid')");
				
				if ($DBObj->affected_rows() >= 1)
					$gStatus = "Data deleted successfully";
				else
					$gStatus = "Unable to delete data";				
			}
						
			$idx++;
		}
	}
}
else if ($gAction == 'addcoords')
{
	$regionid = @SafeQueryData($_REQUEST['region_id']);
	$DBObj->query("INSERT INTO region_coords (region_id) VALUES ('$regionid')");
	
	if ($DBObj->affected_rows() >= 1)
		$gStatus = "New coordinate recorded added";
	else
		$gStatus = "Unable to save data";		
}
else if ($gAction == 'saveres')
{
	
	$region_resource_id = @SafeQueryData($_REQUEST['region_resource_id']);
	$producerlimit = @SafeQueryData($_REQUEST['producerlimit']);
	$producercount = @SafeQueryData($_REQUEST['producercount']);
	$producercost = @SafeQueryData($_REQUEST['producercost']);
	$resourcecapacity = @SafeQueryData($_REQUEST['resourcecapacity']);
	$resourceextracted = @SafeQueryData($_REQUEST['resourceextracted']);
	$productionrate = @SafeQueryData($_REQUEST['productionrate']);
	
	if ($region_resource_id > 0) // save modified info
	{
		$DBObj->query("UPDATE region_resources SET producer_limit='$producerlimit',producer_count='$producercount',producer_cost='$producercost',resource_capacity='$resourcecapacity',resource_extracted='$resourceextracted',production_rate='$productionrate' WHERE (region_resource_id = '$region_resource_id')");
		//print("UPDATE region_resources SET producer_limit='$producerlimit',producer_count='$producercount',producer_cost='$producercost',resource_capacity='$resourcecapacity',resource_extracted='$resourceextracted',production_rate='$productionrate' WHERE (region_resource_id = '$region_resource_id')");		
		
		if ($DBObj->affected_rows() >= 1)
			$gStatus = "Data saved successfully";
	}
	else // insert this sucker
	{
		$region_id = @SafeQueryData($_REQUEST['region_id']);
		$resource_id = @SafeQueryData($_REQUEST['resource_id']);
		$DBObj->query("INSERT INTO region_resources (region_id,resource_id,producer_limit,producer_count,producer_cost,resource_capacity,resource_extracted,production_rate) VALUE ('$region_id','$resource_id','$producerlimit','$producercount','$producercost','$resourcecapacity','$resourceextracted','$productionrate')");

		if ($DBObj->affected_rows() >= 1)
			$gStatus = "New resource recorded added";
		else
			$gStatus = "Unable to save data";			
	}
	
}
else if ($gAction == 'addregion')
{
	$DBObj->query("INSERT INTO regions (name,population) VALUE ('','')");
	$regionid = $DBObj->insert_id();
	$DBObj->query("INSERT INTO region_coords (region_id) VALUES ('$regionid')");
	
		if ($DBObj->affected_rows() >= 1)
			$gStatus = "New region added";
		else
			$gStatus = "Unable to add region";		
}
// *** END PROCESS ACTIONS


// create the resource name array
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
print ("<!-- gAction[$gAction] -->\n");
?>

<html>
<head><title>CS: Region Editor</title></head>

<link rel="stylesheet" href="/css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script>
function ToogleObject(objname)
{
	if (!DHTML) return;

	var x = new getObj(objname);
	var flag = (x.style.display == 'none');
	
	x.style.display = (flag) ? '' : 'none';
	
	return flag;
}
</script>


<body>

<table width=800>
<tr><td><a href="/modcp/editregion.php?formaction=addregion"><input type=button value='Add Region' class=a></a></td></tr>
</table>
<?
if (strlen($gStatus) > 0)
	print "<h2>$gStatus</h2>";

$datatable = $DBObj->query("SELECT * FROM regions ORDER BY region_id");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$regionid= $storeitem["region_id"];
	$region = $regLoader->loadRegion($regionid,true);
	$nation = $natLoader->getNationByRegionID($regionid);

?>	
	<form name=<? echo "regform_$regionid"; ?> method=post action='/modcp/editregion.php'>
	<input type=hidden name='formaction' value='savereg'>
	<input type=hidden name='regionid' value='<? echo $regionid; ?>'>
	<table width=800 border=1 bordercolor=#000000>
	<tr class=highlight><td>Region ID</td><td>Region Name</td><td>Region Population</td></tr>
	<tr><td><b><? echo $regionid; ?></b></td><td><input name='regionname' value='<? echo $region->name; ?>' maxlength=100 style='width:500px'></td><td><input name='regionpopulation' maxlength=10 style='width:100px' value='<? echo $region->population; ?>'>&nbsp;<input type=submit value='Save'></td></tr>
<?
	if ($nation->id != null)
		print ("<tr class=label><td colspan=3>&nbsp;<b>Nation:</b> $nation->name ($nation->id)</td></tr>\n");

?>	
	
	
	
	
	</form>
	
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('regioncoords_<? echo $region->id; ?>'); return false;">Coordinates</a></td></tr>
	<tr><td colspan=3>
	
	<DIV ID="regioncoords_<? echo $region->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=1>
	<form name='regcoords_<? echo $region->id; ?>' method=post action='/modcp/editregion.php'>
	<input type=hidden name='region_id' value='<? echo $regionid; ?>'>
	<input type=hidden name='formaction' value='savecoords'>
<?
	//if (count($region->coordsArray) > 0)
	//{
		$idx = 0;
		foreach ($region->coordsArray as $coord)
		{
			$coordid = $region->coordsIDArray[$idx];
			$coordstr = implode(",",$coord);
			
			print ("<tr><td><input type=checkbox name='coords_check_$coordid'></td><td colspan=2><input name='coords_$coordid' value='$coordstr' style='width:700px'></td></tr>\n");
			
			$idx++;
		}
	//}
?>	
	<tr><td colspan=3><input type=button value='Save' onClick="submit();">&nbsp;<input type=button value='Delete' onClick="document.<? echo "regcoords_$regionid"; ?>.formaction.value='delcoords'; submit();">&nbsp;<input type=button value="Add" onClick="document.<? echo "regcoords_$regionid"; ?>.formaction.value='addcoords'; submit();"></td></tr>
	</form>
	</table>
	</DIV>
	
	</td></tr>
	
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('regionresources_<? echo $region->id; ?>'); return false;">Resources</a></td></tr>
	<tr><td colspan=3>

	<DIV ID="regionresources_<? echo $region->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=0>
<?	
	$idx = 0;
	//foreach ($region->resources as $resource)
	foreach ($resArray as $resobj)
	{
		$resource = $region->resources[$resobj->resource_id];
		
		if ($idx > 0)
			print ("<tr><td colspan=4><hr></td></tr>\n");
?>
	<form name='regres_<? echo $region->id."_".$resource->resource_id; ?>' method=post action='/modcp/editregion.php'>
	<input type=hidden name='formaction' value='saveres'>
	<input type=hidden name='region_resource_id' value='<? echo $resource->region_resource_id; ?>'>
	<input type=hidden name='region_id' value='<? echo $region->id; ?>'>
	<input type=hidden name='resource_id' value='<? echo $resobj->resource_id; ?>'>
<?
		
		
		print ("<tr class=label><td colspan=4>Name: <b>".($resobj->name)."</b></td></tr>\n");
		print ("<tr class=label><td>Max # of Producers</td><td><input type=text value='$resource->producerlimit' name='producerlimit'></td><td>Resource Capacity:</td><td><input type=text value='$resource->resourcecapacity'  name='resourcecapacity'></td></tr>\n");
		print ("<tr class=label><td>Producer Count:</td><td><input type=text value='$resource->producercount' name='producercount'></td><td>Resources Extracted:</td><td><input type=text value='$resource->resourceextracted'  name='resourceextracted'></td></tr>\n");
		print ("<tr class=label><td>Producer Cost:</td><td><input type=text value='$resource->producercost' name='producercost'></td><td>Production Rate:</td><td><input type=text value='$resource->productionrate'  name='productionrate'></td></tr>\n");
		print ("<tr><td colspan=4><input type=submit value='Save'></td></tr>\n");		
?>
	</form>
<?		
		$idx++;		
	}
?>	
	</table>	
	</DIV>
	
	
	
	</td></tr>
	
	</table>
	<br>
<?	
}
?>


</body>
</html>