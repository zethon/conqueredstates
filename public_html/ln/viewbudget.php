<?
//-----------------------------------------------------------------------------
// $RCSFile: viewissues.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/07/14 00:46:38 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./nations.php');
require_once('./users.php');

function GetBudgetEstimates($nation)
{
	$DBObj  = $GLOBALS['DBObj'];
	$gExpenses  = $GLOBALS['gExpenses'];
	$expensetot = 0;
	
	// short and sweet, estimate the expenses
	foreach ($gExpenses as $name=>$text)
	{
		if ($nation->expenses->$name > 0)
			$expensetot += ($nation->ecstats->govtbudg/365) * $nation->expenses->$name;
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
	
	// loop through each resource
	$resprodcost = 0;
	foreach ($resArray as $resobj)
	{
		$nresource = $nation->resources[$resobj->resource_id];
		if ($nresource == null)
			continue;
		
		// calc for each region
		foreach ($nation->regions as $region)
		{
			$rresource = $region->resources[$nresource->resource_id];
			$resprodcost += ($rresource->producercount * $rresource->producercost);
		}
	}

	$govintake = sprintf('%.0f',$nation->ecstats->govtbudg/365);
	$tomorrowest = ($nation->balance + $govintake) - ($expensetot + $resprodcost);		
			
	return array($expensetot,$resprodcost,$govintake,$tomorrowest);
}


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
	WriteLog("WARNING:unathorized attemp to view issues");
	header("Location: $gMainURL");
	exit;
}

$gAction = $_REQUEST['formaction'];
$errorArray = array();
$errored = false;

if ($gAction == 'save')
{
	$expiretime = time()+10;
	
	foreach ($gExpenses as $name=>$text)
	{
		$temp = $_REQUEST[$name];
	
		if (is_numeric($temp) && $temp >= 0)
		{
			$temp *= .01;			
		}
		else
		{
			$errorArray[$name] = $_REQUEST[$name];
			$errored = true;
		}
		
		$nation->expenses->setProp($name,$temp);
	}
	
	$newtax = $_REQUEST['taxrate'];
	
	if (is_numeric($newtax) && $newtax >= 0 && $newtax <= 100)
	{
		$newtax *= .01;	
	}
	else
	{
		$errorArray['taxrate'] = 1;
		$errored = true;
	}
	
	$nation->setProp('taxrate',$newtax);
	
	if (!$errored)
	{
		$DBObj->query("UPDATE nation SET tax='$newtax' WHERE (nation_id = '$nation->id')");
		$nation->expenses->Save();	
	}
}

$nation->CalcEconomicStats();
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States</title>

<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">
<script src="/clientscript/global.js" type="text/javascript"></script>

<script type="text/javascript">
<!--
<?
	echo "\tvar fields = new Array(".(count($gExpenses)).")\n";
	$idx = 0;
	foreach ($gExpenses as $name=>$text)
	{
		echo "\tfields[$idx] = \"$name\";\n";
		$idx++;		
	}
?>


function updatePercent()
{
	var total = 0;
	for (idx in fields)
		total += parseInt(document.expenses.elements[fields[idx]].value);
	
	document.expenses.total.value = total;
}
-->
</script>

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

<table cellspacing=0 cellpadding="1">
<tr><td>

<form name="expenses" method="post" action="viewbudget.php">
<input name=formaction type="hidden" value="save">
<table width=350 cellpadding="0" cellspacing="0">
<tr><td class="highlight" colspan="2">National Tax Rate</td>
<tr><td colspan=2>&nbsp;</td></tr>
<?

	if ($errorArray['taxrate'] == 1)
	{
		print ("<tr><td colspan=2 class=error_text>You entered an invalid value. Please enter a percentage 0 to 100.</td></tr>");
		$taxr = $nation->taxrate;
	}
	else
		$taxr = number_format($nation->taxrate*100)

?>

<tr><td width=125 class=bold_label>Tax Rate:</td><td><input style="width:35px" type=text name=taxrate maxlength=3 value='<? echo $taxr; ?>'>%</td></tr>
<tr><td colspan=2>&nbsp;</td></tr>
<tr><td class="highlight" colspan="2">Goverment Expenditures</td>
<tr><td colspan=2>&nbsp;</td></tr>
<?
$total = 0;
foreach ($gExpenses as $name=>$text)
{
	if (strtolower($name) == 'nation_expense_id' || is_object($nation->expenses->$name) || is_array($nation->expenses->$name))
		continue;

	$total += $nation->expenses->$name;
	$daily = number_format(sprintf('%.0f',(($nation->ecstats->govtbudg/365) * $nation->expenses->$name)));
	$annual = number_format(sprintf('%.0f',(($nation->ecstats->govtbudg) * $nation->expenses->$name)));

	if (strlen($errorArray[$name]) > 0)
	{
		$val = $errorArray[$name];
		print ("<tr><td colspan=2 class=error_text>You entered an invalid value. Please enter a percentage 0 or greater.</td></tr>");
	}
	else
		$val = number_format($nation->expenses->$name * 100);				

	print ("<tr><td width=125 class=bold_label>$text:</td><td><input onKeyUp=\"updatePercent();\" style=\"width:35px\" type=text name='$name' id='$name' maxlength=3 value='$val'>%</td></tr>\n");
	print ("<tr><td colspan=2><table width=100% cellpadding=\"0\" cellspacing=\"0\"><tr><td width=50%>Daily budget in ".(ucfirst($nation->currency))."s:</td><td>$nation->currency_symbol"."$daily</td></tr></table></td></tr>\n");
	print ("<tr><td colspan=2><table width=100% cellpadding=\"0\" cellspacing=\"0\"><tr><td width=50%>Annual budget in ".(ucfirst($nation->currency))."s:</td><td>$nation->currency_symbol"."$annual</td></tr></table></td></tr>\n");
	print ("<tr><td colspan=2>&nbsp;</td></tr>\n");
}
print ("<tr><td class=bold_label>Total:</td><td><input name='total' readonly='true' value='".(number_format($total*100))."' style=\"width:35px\">%</td></tr>\n");
print ("<tr><td colspan=2>&nbsp;</td></tr>\n");
if ($total > .9)
{
	print ("<tr><td colspan=2 class=label><div class=error_text>WARNING!</div>Your expenses exceed 90% of your GDP. You may want to free up some money for your resource management. To manage your resources, click on the \"Resources\" link.");
	print ("<tr><td colspan=2>&nbsp;</td></tr>\n");
}
?>
<tr><td colspan="2" align="left"><input type=submit value="Save"></td></tr>
</table>
</form>

</td>
<td>&nbsp;</td>
<td valign="top" align=left>
<?
	list ($expensetotal,$resprodcost,$govintake,$tomorrowest) = GetBudgetEstimates($nation);
	//$govintake = sprintf('%.0f',$nation->ecstats->govtbudg/365);
	//$tomorrowest = ($nation->balance + $govintake) - ($expensetotal + $resprodcost);
	
	$current = "label";
	if ($nation->balance < 0)
		$current = "error_text";
		

	$tomw = "label";
	if ($tomorrowest < 0)
		$tomw = "error_text";	
		
?>

<table width="350" class=border_table2>
<tr><td class=highlight colspan="2">Vital Stats</td></tr>
<tr><td class="bold_label">Population:</td><td align=right><? echo number_format($nation->GetPopulation()); ?></td></tr>
<tr><td class="bold_label">National GDP:</td><td align=right><? echo  $nation->currency_symbol.(number_format($nation->ecstats->gdp)); ?></td></tr>
<tr><td class="bold_label">GDP per Capita:</td><td align=right><? echo $nation->currency_symbol.number_format($nation->ecstats->gdppc); ?></td></tr>
<tr><td class="bold_label">Taxrate:</td><td align=right><? echo  number_format($nation->taxrate*100); ?>%</td></tr>
<tr><td colspan="2"><hr></td></tr>
<tr><td class="bold_label">Tax Revenue (Daily):</td><td class=label align=right><? echo  $nation->currency_symbol.(number_format($govintake)); ?></td></tr>
<tr><td class="bold_label">Expenditures Total (Daily):</td><td class=label align=right><? echo  $nation->currency_symbol.(number_format($expensetotal)); ?></td></tr>
<tr><td class="bold_label">Resource Expenses (Daily):</td><td class=label align=right><? echo  $nation->currency_symbol.(number_format($resprodcost)); ?></td></tr>
<tr><td class="bold_label">Current Balance:</td><td class=<? echo $current; ?>  align=right><? echo  $nation->currency_symbol.(number_format($nation->balance)); ?></td></tr>
<tr><td class="bold_label">Tomorrow's Estimated Balance:</td><td class=<? echo $tomw; ?>  align=right><? echo  $nation->currency_symbol.(number_format($tomorrowest)); ?></td></tr>
</table>

</td>
</tr></table>


</td>
<!-- /MAIN CONTENT -->
</tr>



</table>

</body>
</html>