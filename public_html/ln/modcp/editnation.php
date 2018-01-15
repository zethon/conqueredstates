<?
//-----------------------------------------------------------------------------
// $Workfile: editnation.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/05/27 03:17:08 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNINGS);

chdir('./../');
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./nations.php'); 
require_once('./resources.php'); 

$natLoader = new NationLoader();
$regLoader = new RegionLoader();
$resourceConCalc = new ResConsumptionCalc();

$gAction = $_REQUEST['formaction'];

if ($gAction == 'savenation')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);	
	
	if ($nationid > 0)
	{
		$titleid = @SafeQueryData($_REQUEST['title']);	
		$nationname = @SafeQueryData($_REQUEST['nationname']);
		$motto = @SafeQueryData($_REQUEST['motto']);
		$currency = @SafeQueryData($_REQUEST['currency']);
		$balance = @SafeQueryData($_REQUEST['balance']);
		$taxrate = @SafeQueryData($_REQUEST['taxrate']);

		$DBObj->query("UPDATE nation SET name='$nationname',tax='$taxrate',balance='$balance',motto='$motto',currency='$currency',nation_title='$titleid' WHERE (nation_id = '$nationid')");
		
		if ($DBObj->affected_rows() >= 1)
			$gStatus = "Data saved successfully";
	}
	
}
else if ($gAction == 'addregions')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);
	$newregion = @SafeQueryData($_REQUEST['newregionid']);
	
	if (strlen($nationid) > 0 && strlen($newregion) > 0)
	{
		$tempreg = $regLoader->loadRegion($newregion);
		$tempnat = $natLoader->getNationByRegionID($newregion);
		
		if ($tempreg == null)
			$gStatus = "Unknown Region";	
		else if ($tempnat->id != null)
			$gStatus = "Region already assigned";	
		else
		{
			$DBObj->query("INSERT INTO nation_regions (nation_id,region_id) VALUE ('$nationid','$newregion')");
			
			if ($DBObj->affected_rows() >= 1)
				$gStatus = "New region added to nation";
			else
				$gStatus = "Could not add region";
		}
	}
}
else if ($gAction == 'delregion')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);
	$nation = $natLoader->loadNation($nationid);
	
	if ($nation != null)
	{
		foreach ($nation->regions as $region)
		{
			$checkname = "reg_".$region->id;
			$checkval = @SafeQueryData($_REQUEST[$checkname]);
			
			if ($checkval == 'on')
			{
				$DBObj->query("DELETE FROM nation_regions WHERE (nation_id='$nationid') AND (region_id='$region->id')");

				if ($DBObj->affected_rows() >= 1)
					$gStatus = "Region removed";
				else
					$gStatus = "Could not remove region";				
			}
		}		
	}
}
else if ($gAction == 'saveres')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);
	$resourceid = @SafeQueryData($_REQUEST['resourceid']);
	$nationresourceid = @SafeQueryData($_REQUEST['nationresourceid']);
	$resquantity = @SafeQueryData($_REQUEST['resquantity']);
	
	if ($nationresourceid > 0 && strlen($resquantity) > 0) // update
	{
		$DBObj->query("UPDATE nation_resource_inventory SET quantity='$resquantity' WHERE (nation_resource_inventory_id = '$nationresourceid')");
		
		if ($DBObj->affected_rows() >= 1)
			$gStatus = "Data saved";
		else
			$gStatus = "Could not save data";				
	}
	else if ($nationid > 0 && $resourceid > 0 && strlen($resquantity) > 0) // insert
	{
		$DBObj->query("INSERT INTO nation_resource_inventory (nation_id,resource_id,quantity) VALUE ('$nationid','$resourceid','$resquantity')");
		
		if ($DBObj->affected_rows() >= 1)
			$gStatus = "Data saved";
		else
			$gStatus = "Could not save data";						
	}
}
else if ($gAction == 'savestats')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);
	$nation = $natLoader->loadNationWithStats($nationid);
	
	// build the data string
	$datastr = "";
	$vars = get_object_vars($nation->stats);
	foreach ($vars as $name=>$val) 
	{
		if ($name == 'statsid')
			continue;
			
		if (strlen($datastr) > 0)
			$datastr .= ",";		
		
		$val = @SafeQueryData($_REQUEST[$name]);
		// TODO: validate $val is sage
		
		$datastr .= "$name='$val'";
	}
	
	$query = "UPDATE nation_stats SET $datastr WHERE (nation_stats_id = '".($nation->stats->statsid)."')";
	$DBObj->query($query);

	if ($DBObj->affected_rows() >= 1)
		$gStatus = "Data saved";
	else
		$gStatus = "Could not save data";		
	
	unset($nation);
} 
else if ($gAction == 'saveexpenses')
{
	$nationid = @SafeQueryData($_REQUEST['nationid']);
	$nation = $natLoader->loadNationWithStats($nationid);

	// build the data string
	$datastr = "";
	$vars = get_object_vars($nation->expenses);
	foreach ($vars as $name=>$val) 
	{
		if ($name == 'statsid')
			continue;
			
		if (strlen($datastr) > 0)
			$datastr .= ",";		
		
		$val = @SafeQueryData($_REQUEST[$name]);
		// TODO: validate $val is sage
		
		$datastr .= "$name='$val'";
	}
	
	$query = "UPDATE nation_expenses SET $datastr WHERE (nation_id = '".($nation->id)."')";
	$DBObj->query($query);

	if ($DBObj->affected_rows() >= 1)
		$gStatus = "Data saved";
	else
		$gStatus = "Could not save data";		
	
	unset($nation);	
}


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
<head><title>CS: Nation Editor</title></head>

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

<?
if (strlen($gStatus) > 0)
	print "<h2>$gStatus</h2>";


$datatable = $DBObj->query("SELECT * FROM nation LEFT JOIN users on (nation.nation_id = users.country_id)ORDER BY nation_id");
while ($storeitem = $DBObj->fetch_array($datatable))
{
	$nation = $natLoader->loadNationWithStats($storeitem['nation_id']);
	$username = $storeitem['username'];
	$userid = $storeitem['userid'];
	
?>
	<table width=800 border=1 bordercolor=#000000 align=center>
	<tr><td colspan=3><h2><? echo "The $nation->title of $nation->name"; ?></h2></td></tr>
	
	<form action='/modcp/editnation.php' method='post' name='nationinfo'>
	<input type=hidden name=formaction value=savenation>
	<input type=hidden name=nationid value='<? echo $nation->id; ?>'>
	<tr class=highlight><td>Nation ID</td><td>Nation Name</td><td>User (User ID)</td></tr>
	<tr class=label><td width=10%><? echo $nation->id; ?></td><td width=70%><? printTitleDropDown1($nation->titleid); ?>&nbsp;<input style='width:325px' type=text value='<? echo $nation->name; ?>' name=nationname></td><td><? echo "$username ($userid)"; ?></td></tr>
	<tr class=label><td>&nbsp;</td><td colspan=2>Motto:&nbsp;<input name=motto type=text maxlength=100 style='width:325px' value="<? echo $nation->motto; ?>">&nbsp;Currency:&nbsp;<input name=currency type=text value='<? echo $nation->currency; ?>' maxlength=10></td></tr>
	<tr class=label><td><input type=submit value='Save'></td><td colspan=2>Balance:&nbsp;<input name=balance type=text style='width:175px' value='<? echo $nation->balance; ?>'>&nbsp;Tax Rate:&nbsp;<input name=taxrate type=text value='<? echo $nation->taxrate; ?>'></td></tr>
	</form>

	<!-- START STAT CODE -->
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('natstats_<? echo $nation->id; ?>'); return false;">Stats</a></td></tr>
	<tr><td colspan=3>
	
	<DIV ID="natstats_<? echo $nation->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=1>
	<form name=natstatsfrm_<? echo $nation->id; ?> method=post action='/modcp/editnation.php'>
	<input type=hidden name='formaction' value='savestats'>
	<input type=hidden name=statsid value='<? echo $nation->stats->statsid;; ?>'>
	<input type=hidden name=nationid value='<? echo $nation->id; ?>'>
<?	
	// turn the NationStats object into an arry
	$vars = get_object_vars($nation->stats);
	foreach ($vars as $name=>$val) 
	{
		if ($name == 'statsid')
			continue;
	
    echo "<tr class=label><td width=20%>$name</td><td><input name='$name' type=input value='$val'></td></tr>\n";
	} 
?>
	<tr><td colspan=2><input type=submit value='Save'></td></tr>
	</form>
	</table>
	<DIV>
	
	</td></tr>
	<!-- END STAT CODE -->


	<!-- BEGIN EXPENSES CODE -->
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('natexpenses_<? echo $nation->id; ?>'); return false;">Expenses</a></td></tr>
	<tr><td colspan=3>	
	<DIV ID="natexpenses_<? echo $nation->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=1>
	<form name=natstatsfrm_<? echo $nation->id; ?> method=post action='/modcp/editnation.php'>
	<input type=hidden name='formaction' value='saveexpenses'>	
	<input type=hidden name=nationid value='<? echo $nation->id; ?>'>
<?
	$vars = get_object_vars($nation->expenses);
	foreach ($vars as $name=>$val) 
	{
	    echo "<tr class=label><td width=20%>$name</td><td><input name='$name' type=input value='$val'></td></tr>\n";	
	}
?>
	<tr><td colspan=2><input type=submit value='Save'></td></tr>	
	</form>
	</table>
	</DIV>
	</td></tr>
	<!-- /END EXPENSES CODE -->



	<!-- START REGION CODE -->		
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('natregions_<? echo $nation->id; ?>'); return false;">Regions</a></td></tr>
	<tr><td colspan=3>
	
	<DIV ID="natregions_<? echo $nation->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=1>
	<form action='/modcp/editnation.php' method=post name="nation_regions_<? echo $nation->id; ?>">
	<input type=hidden name=formaction value=''>
	<input type=hidden name=nationid value='<? echo $nation->id; ?>'>
	<tr class=label><td><b>Region ID</b></td><td><b>Nation Name</b></td><td><b>Population</b></td></tr>
<?
	$idx = 0;
	foreach ($nation->regions as $region)
	{
		if (($idx % 2) == 0)
			$class = "gridbg2";
		else
			$class = "gridbg1";
			
		print ("<tr class=$class><td><input type=checkbox name='reg_$region->id'>$region->id</td><td>$region->name</td><td>$region->population</td></tr>\n");
		
		$idx++;
	}
?>
	<tr><td colspan=3><input type=button value='Delete' onClick="document.nation_regions_<? echo $nation->id; ?>.formaction.value='delregion'; submit();">&nbsp;<input type=button value='Add' onClick="ToogleObject('natregions_addregion_<? echo $nation->id; ?>');"></td></tr>
	
	<tr><td colspan=3>
		<DIV ID="natregions_addregion_<? echo $nation->id; ?>" style="display:none">
		<table width=100% cellspacing=0 cellpadding=0>
		<tr class=label><td><b>New Region ID:</b>&nbsp;<input type=text maxlength=5 name='newregionid'>&nbsp;<input type=button value='Save' onClick="document.nation_regions_<? echo $nation->id; ?>.formaction.value='addregions'; submit();"></td></tr>
		</table>
		</DIV>
	</td></tr>
	</form>
	</table>
	</DIV>
	</td></tr>
	<!-- END REGION CODE -->
	
	<!-- START RESOURCE CODE -->
	<tr class=highlight><td colspan=3 align=center><a href="#" onClick="ToogleObject('natres_<? echo $nation->id; ?>'); return false;">Resources</a></td></tr>
	<tr><td colspan=3>
	
	<DIV ID="natres_<? echo $nation->id; ?>" style="display:none"> 
	<table width=100% cellspacing=0 cellpadding=1>
<?
	$idx=0;
	foreach ($resArray as $resource)
	{
		$nresource = $nation->resources[$resource->resource_id];
		
		// figure out the consumption of the resource
		$resourceConCalc->setProp('nation',$nation);
		$yearly = ceil($resourceConCalc->CalculateConsumption($nresource->resource_id));		
		$daily = ceil($yearly/365);
?>		
		<form name='nat_res_<? echo $nation->id."_".$resource->resource_id; ?>' method=post action='/modcp/editnation.php'>
		<input type=hidden name=formaction value='saveres'>
		<input type=hidden name=nationid value='<? echo $nation->id; ?>'>
		<input type=hidden name=resourceid value='<? echo $resource->resource_id; ?>'>
		<input type=hidden name=nationresourceid value='<? echo $nresource->quantity_id; ?>'>		
<?	
		print ("<tr class=label><td colspan=4>Name: <b>$resource->name</b>($resource->resource_id)</td></tr>\n");		
		print ("<tr class=label><td colspan=4>Reserves:&nbsp;<input name=resquantity type=text value='$nresource->quantity' style='width:100px' maxlength=10> $resource->unit"."s&nbsp;<input type=submit value='Save'></td></tr>\n");		
		print ("<tr class=label><td colspan=4>Consumption Per Year: <b>".(number_format($yearly))."</b>&nbsp;|&nbsp;Per Day: <b>".(number_format($daily))."</b></td></tr>\n");
		print ("<tr><td colspan=4><hr></td></tr>\n");
		print ("</form>");
	}	
?>

	</table>
	<DIV>
	
	</td></tr>
	<!-- END RESOURCE CODE -->	
	
	</table>
	<br>
	<hr width=800 color=000000>
	<br>
<?
}
?>

</body>
</html>