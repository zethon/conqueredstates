<?
//-----------------------------------------------------------------------------
// $RCSFile: index.php $ $Revision: 1.17 $ $Author: addy $ 
// $Date: 2006/07/14 02:34:05 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./regions.php');
require_once('./nations.php');
require_once('./users.php');
require_once('./maps.php');
require_once('./description.php');

function GetGoodness($val)
{
	$retval = "Unknown";
	
	if ($val < 1)
		$retval = "Abysmal";
	else if ($val < 2)
		$retval = "Very Poor";
	else if ($val < 3)
		$retval = "Poor";
	else if ($val < 4)
		$retval = "Below Average";
	else if ($val < 7)
		$retval = "Average";
	else if ($val < 8)
		$retval = "Above Average";
	else if ($val < 9)
		$retval = "Excellent";
	else if ($val <= 10)
		$retval = "Amazing";
		
	return $retval;		
}


$userloader = new UserLoader();
$gUser = $userloader->loadCookieUser();

if ($gUser != null)
{
	$nloader = new NationLoader();
	$nation = $nloader->loadNationWithStats($gUser->countryid);
	
	$lastlogin = strtotime($gUser->lastlogin);
	
	if ((time() - $lastlogin) > (60*60*24))
		$gUser->UpdateLogin();
}

if ($nation == null)
{
	header("Location: $gMainURL"."/viewnation.php");
	exit;
}

$nation->CalcEconomicStats();
$desc = new NationDesc();		
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>Conquered States</title>
<link rel="stylesheet" href="css/style.css" type="text/css">
<link rel="stylesheet" href="css/main.css" type="text/css">
</head>

<body>

<table class=highlight width=100% cellpadding=1 cellspacing=1>
<tr valign=top><td>
<table width=100% align=center cellspacing=0 cellpadding=0 bgcolor=#DEE7F7>
<!-- BANNER CELL -->
<tr><td colspan=4 align=center>
<? include "./includes/banner.inc"; ?>
</td></tr>
<!-- /BANNER CELL -->
</table>
</td></tr>
</table>

<br>

<table width=100% align=center style="height: 75%">
<tr valign=top>
<!-- LEFT SIDE MENU -->
<td width=109 valign=top>
<? include "./includes/sidemenu.inc"; ?>
</td>
<!-- /LEFT SIDE MENU -->

<td>&nbsp;</td>

<!-- MAIN BODY CELL -->
<td align=center>

<table width=100% align=center>
<tr><td align=left><img src="viewflag.php?id=<? echo $nation->flagid; ?>" alt="<? echo $nation->name; ?> National Flag" border=1></td><td valign=top class=nation_banner_text align=center><? echo "The ".$nation->title." of ".ucfirst($nation->name)."<br><div class=label>\"$nation->motto\"</div>"; ?></td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 align=center class=label>Map of <? echo ucfirst($nation->name); ?><br><img border=1 src="<? echo "viewmap.php?action=viewnation&amp;id=".$nation->id; ?>" alt="<? echo $nation->name; ?> National Map"></td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2><table align=center width=85%><tr><td class=label><b>Overview</b><br><? echo $desc->GetGenericDescription($nation); ?></td></tr></table></td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 width=100%>
	<table width=100%>
		<tr class=label align=center><td><b>Economy:</b> <? echo GetGoodness(($nation->stats->economic_strength+$nation->stats->economic_health)/2); ?></td><td><b>Civil Liberties:</b> <? echo GetGoodness($nation->stats->civil_liberties); ?></td><td><b>Political Freedom: </b><? echo GetGoodness($nation->stats->political_freedom); ?></td></tr>
	</table>
</td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 width=100%>
	<table width=100%>
		<tr class=label align=center valign=top><td><b>GDP</b> (in <? echo $nation->currency; ?>):<br>&nbsp;<? echo $nation->currency_symbol; ?><? echo number_format($nation->ecstats->gdp); ?></td><td><b>GDP per Capita</b> (in <? echo $nation->currency; ?>):<br>&nbsp;<? echo $nation->currency_symbol; ?><? echo number_format($nation->ecstats->gdppc); ?></td><td><b>Exchange Rate</b>:<br><? echo $nation->currency_symbol; ?>1 <? echo $nation->currency; ?> = $<? echo number_format($nation->ecstats->exchrate,2, '.', ','); ?> USD<br><? echo $nation->currency_symbol; ?><? echo number_format(1/$nation->ecstats->exchrate, 2, '.', ',')." ".$nation->currency; ?> = $1.00 USD</td></tr>
	</table>
</td></tr>
<tr><td colspan=2><hr></td></tr>
</table>

<br>

<!--<tr><td>Population:</td><td><? echo number_format($nation->GetPopulation()); ?></td></tr>
<tr><td>Motto:</td><td><? echo $nation->motto; ?></td></tr>
<tr><td>Currency:</td><td><? echo $nation->currency; ?></td></tr>
<tr><td>TaxRate:</td><td><? echo ($nation->taxrate * 100); ?>%</td></tr>
<tr><td colspan=2><hr></td></tr>-->
<!--<tr class=label><td>Gov't Effeciency:</td><td><? echo number_format($nation->ecstats->govteff * 100); ?>%</td></tr>-->
<!--<tr class=label><td>Gov't Waste:</td><td><? echo number_format($nation->ecstats->govtwaste); ?></td></tr>-->
<!--<tr class=label><td>Unemployment:</td><td><? echo number_format($nation->ecstats->unemployment*100); ?>%</td></tr>-->
<!--<tr class=label><td>Consumer Confidence:</td><td><? echo number_format($nation->ecstats->consconf*100); ?>%</td></tr>-->
<!--<tr class=label><td><b>Consumption:</b></td><td><? echo number_format($nation->ecstats->consumption); ?></td></tr>-->
<!--<tr class=label><td><b>Gov't Budget:</b></td><td><? echo number_format($nation->ecstats->govtbudg); ?></td></tr>-->
<!-- /NATION INFO -->


<table width=100%>
<tr valign=top>
<td width=50% align=center>
	<table cellpadding=0 cellspacing=1 bgcolor=#000000>
		<tr><td>
			<table width=100% bgcolor=#DEE7F7 cellspacing=0 style="height:100%">
				<tr><td colspan=2 align=center class=highlight><? echo $nation->name; ?> Info</td></tr>
				<tr class=gridbg2><td colspan=2 class=tiny_text align=center>(all monetary amounts are in <? echo $nation->currency ?>s)</td></tr>
				<tr class=gridbg1><td>Population:</td><td><? echo number_format($nation->GetPopulation()); ?></td></tr>
				<!--<tr class=gridbg1><td>Tomorrow's Population:</td><td><? echo number_format($nation->GetPopulation()+floor(($nation->GetGrowthRate()*$nation->GetPopulation())/365), 0, '.', ','); ?>%</td></tr>-->
				<tr class=gridbg2><td>Unemployment:</td><td><? echo number_format($nation->ecstats->unemployment*100); ?>%</td></tr>
				<tr class=gridbg1><td>Currency</td><td><? echo $nation->currency; ?></td></tr>
				<tr class=gridbg2><td>GDP:</td><td><? echo  $nation->currency_symbol.(number_format($nation->ecstats->gdp)); ?></td></tr>
				<tr class=gridbg1><td>Gov't Budget:</td><td><? echo $nation->currency_symbol.(number_format($nation->ecstats->govtbudg)); ?></td></tr>
				<tr class=gridbg2><td>GDP per Capita:</td><td><? echo number_format($nation->ecstats->gdppc); ?></td></tr>
				<tr class=gridbg1><td>Exchange Rate:</td><td><? echo $nation->currency_symbol; ?>1.00 <? echo $nation->currency; ?> = $<? echo number_format($nation->ecstats->exchrate,2, '.', ','); ?> USD</td></tr>
				<tr class=gridbg1><td>&nbsp;</td>       <td><? echo $nation->currency_symbol.(number_format(1/$nation->ecstats->exchrate, 2, '.', ','))." ".$nation->currency; ?> = $1.00 USD</td></tr>
			</table>
		</td></tr>
	</table>
</td>

<td align=center>

	<table cellpadding=0 cellspacing=1 bgcolor=#000000><tr><td>
		<table width=100% bgcolor=#DEE7F7 cellspacing=0 style="height:100%">
			<tr><td colspan=2 align=center class=highlight>National Budget</td></tr>
			<tr><td colspan=2 class=tiny_text>(by percentage of gov't budget)</td></tr>
<?
	$i = 0;
	foreach ($nation->expense_names as $name)
	{
		if ($i % 2)
			$grid = "gridbg1";
		else
			$grid = "gridbg2";
			
		print ("<tr class=$grid><td>".ucfirst($name)."</td><td>".($nation->expenses->$name * 100)."%</td></tr>");
		$i++;
	}
?>
		</table></td></tr>
	</table>

</td>
</tr>

<tr><td>&nbsp;</td></tr>

<tr>
<td colspan=2 align=center>

<table cellpadding=0 cellspacing=1 bgcolor=#000000><tr><td>
<table width=100% bgcolor=#DEE7F7 cellspacing=0 style="height:100%">
<tr><td colspan=3 align=center class=highlight>National Resources</td></tr>
<?
	$i = 0;
	foreach ($nation->resources as $nresource)
	{
		if ($i % 2)
			$grid = "gridbg1";
		else
			$grid = "gridbg2";
			
		print ("<tr class=$grid><td>".ucfirst($nresource->name)."</td><td>&nbsp;</td><td>$nresource->quantity $nresource->unit"."s on hand</td></tr>");
		$i++;
	}
?>
</table>
</td></tr></table>

</td>
</tr>

</table>

<br>
<hr width=50%>

<center>
<div class=tiny_text>World Map</div>
<img src="<? echo "$gImagesURL/small-$gNationWorldMap"; ?>" border=1 width=550 height=219 alt="World Nation Map">
</center>

</td>
<!-- /MAIN BODY CELL -->
</tr>
</table>


<br>

<!-- WHO'S ONLINE CODE -->
<?
	$data = $DBObj->query("SELECT * FROM `users` LEFT JOIN nation on (users.country_id = nation.nation_id) LEFT JOIN nation_titles on (nation.nation_title = nation_titles.nation_title_id) WHERE UNIX_TIMESTAMP(lastlogin) != 0 AND (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(lastlogin) < (60 * 30))");
	$numusers = $DBObj->affected_rows();
	
	$userlist = "";
	while ($storeitem = $DBObj->fetch_array($data))
		$userlist .= " ".$storeitem[username]." (<a href='viewnation.php?nationname=".strtolower($storeitem[name])."'>The ".$storeitem[nation_title_text]." of ".$storeitem[name]."</a>),";

	$userlist{strlen($userlist)-1} = " ";
?>
<table width=100% class=border_table2>
	<tr><td class=row_title>&nbsp;User's Currently Signed On: (<? echo $numusers; ?>)</td></tr>
	<tr><td class=gridbg1>&nbsp;<? echo $userlist; ?></td></tr>
</table>
<!-- /WHO'S ONLINE CODE -->

<? echo "<!-- qc:[$query_count] -->"; ?>
</body>
</html>