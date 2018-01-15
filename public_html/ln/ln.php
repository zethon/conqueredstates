<?php
//-----------------------------------------------------------------------------
// $RCSfile: ln.php,v $ $Revision: 1.9 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------

error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 

$action = $_REQUEST['action'];


?>
<form method=post action=ln.php>
<input type=hidden name=action value=calc>

<table>
<tr><td>

<table>
<tr><td>Nation's Name:</td><td><input type=input name=name value='<? echo eo($_REQUEST['name'],'default'); ?>'></td></tr>
<tr><td>Population:</td><td><input type=input name=population value="<? echo eo($_REQUEST['population'],rand(50000,150000)); ?>"></td></tr>
<tr><td>Tax:</td><td><input type=input name=tax value="<? echo eo($_REQUEST['tax'],'.20'); ?>"></td></tr>
<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2><i>(ANY number (decimals too!) between 0 and 10)</i></td></tr>
<tr><td>Economic Stength</td><td><input type=input name=es value=<? echo eo($_REQUEST['es'],'2.5'); ?>></td></tr>
<tr><td>Economic Health</td><td><input type=input name=eh value=<? echo eo($_REQUEST['eh'],'5'); ?>></td></tr>
<tr><td>Civil Liberties</td><td><input type=input name=cl value=<? echo eo($_REQUEST['cl'],'5'); ?>></td></tr>
<tr><td>Political Freedom</td><td><input type=input name=pf value=<? echo eo($_REQUEST['pf'],'5'); ?>></td></tr>
<tr><td colspan=2><input type=submit></td></tr>
</table>

</td>

<td valign=top>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>

<td valign=top>

<table>
<tr><td colspan=2><i>Enter percentage of gov't budget you want to spend on each category</i></td></tr>
<tr><td>administration</td><td><input type=input name=admin value=<? echo eo($_REQUEST['admin'],'.12'); ?>></td></tr>
<tr><td>welfare</td><td><input type=input name=welfare value=<? echo eo($_REQUEST['welfare'],'.12'); ?>></td></tr>
<tr><td>healthcare</td><td><input type=input name=healthcare value=<? echo eo($_REQUEST['healthcare'],'.12'); ?>></td></tr>
<tr><td>education</td><td><input type=input name=education value=<? echo eo($_REQUEST['education'],'.12'); ?>></td></tr>
<tr><td>military</td><td><input type=input name=military value=<? echo eo($_REQUEST['military'],'.12'); ?>></td></tr>
<tr><td>religion</td><td><input type=input name=religion value=<? echo eo($_REQUEST['religion'],'.12'); ?>></td></tr>
<tr><td>law & order</td><td><input type=input name=lo value=<? echo eo($_REQUEST['lo'],'.12'); ?>></td></tr>
<tr><td>environment</td><td><input type=input name=environment value=<? echo eo($_REQUEST['environment'],'.12'); ?>></td></tr>
</table>



</td></tr>
</table>

</form>
<?

if ($action == "calc")
{
	echo "<hr>";
	PrintEconomyFigures($_REQUEST['name'],$_REQUEST['population'],$_REQUEST['tax'],$_REQUEST['es'],$_REQUEST['eh'],$_REQUEST['cl'],$_REQUEST['pf']);	
}


function PrintEconomyFigures($name,$population,$tax,$economic_strength,$economic_health,$civil_liberties,$political_freedom)
{
	if ($tax > 1) $tax = 1;
	if ($economic_strength > 10) $economic_strength = 10;
	if ($economic_health > 10) $economic_health = 10;
	if ($civil_liberties > 10) $civil_liberties = 10;
	if ($political_freedom > 10) $political_freedom = 10;	

	if ($tax < 0) $tax = 0;
	if ($economic_strength < 0) $economic_strength = 0;
	if ($economic_health < 0) $economic_health = 0;
	if ($civil_liberties < 0) $civil_liberties = 0;
	if ($political_freedom < 0) $political_freedom = 0;	
	
	# economy measurement (100 thru 35000) Good = 7500
	$prod = 35000*($economic_strength*.1);
	
	# civil rights measurement (-0.1 thru 0.05) Average = 0
	$con_conf_civil = (($civil_liberties*(15/10))-10)*.01;
	
	# economy measurement (-0.1 thru 0.05) Good = 0
	$con_conf_econ = (($economic_health*(15/10))-10)*.01;
	
	# consumer confidence (+1?)
	$con_conf = 1 + $con_conf_civil + $con_conf_econ;
	
	# gov't effeciency (based on political freedom?) (-0.08 thru -0.1) Average = -0.04
	$govt_eff = 1 + ((($political_freedom*(15/10))-15.1)*.01);
	
	# calculate work enthusiasm! 
	$work_enth_tax = array(0 =>0.02,1 =>0,2 =>-0.02,3 =>-0.04,4 =>-0.06,5 =>-0.08,6 =>-0.1,7 =>-0.12,8 =>-0.14,9 =>-0.16,10 =>-0.18);
	
	# worker enthusiasm (economically) (-0.07 thru 0.03) "Good" = 0
	$work_enth_econ = (($economic_health*(11/10))-7)*.01;
	
	# worker enthusiasm (civil rights) -0.1 thru 0.05, "Average" = 0
	$work_enth_civil = (($civil_liberties*(15/10))-10)*.01;
	
	# worker enthusiasm (politica freedoms) -0.04 thru 0.01, "Good" = 0
	$work_enth_pol = (($political_freedom*(6/10))-4)*.01;	
	
	# calculations
	$work_enth = 1 + $work_enth_tax[ceil($tax * 10)] + $work_enth_econ + $work_enth_civil + $work_enth_pol;
	$out = $prod * $population * $work_enth * $con_conf;
	$cons = $out - $out * $tax;
	$budget = $out * $govt_eff * ($tax + $con_conf * 0.1 + $work_enth * 0.025);
	$govt_exp = $budget * $govt_eff;
	$gdp = $cons + $govt_exp;
	$gdppc = $gdp/$population;
	$unemployment = (1 / (8 * pow(10, 9)) * pow($gdppc - 37500, 2) + 0.0000015 * abs($gdppc - 37500) + 0.03);
	$ex_rate = sqrt($gdppc * $prod / 404000000);
	
	# real consumer confidence 
	$real_cons_conf = (($con_conf-1) + .14)/.23;
	if ($real_cons_conf > .99) {$real_cons_conf = .99;}
	if ($real_cons_conf < .01) {$real_cons_conf = .01;}
	
	echo ("<table>");
	echo ("<tr><td><b>Counrty Name</b></td><td align=right>$name</td></tr>");
	echo ("<tr><td>Population</td><td align=right>".number_format($population)."</td></tr>");
	echo ("<tr><td>Tax Rate</td><td align=right>".($tax * 100)."%</td></tr>");
	echo ("<tr><td>Economic Strength</td><td align=right>".$economic_strength."</td></tr>");
	echo ("<tr><td>Economic Health</td><td align=right>".$economic_health."</td></tr>");
	echo ("<tr><td>Civil Liberties</td><td align=right>".$civil_liberties."</td></tr>");
	echo ("<tr><td>Political Freedom</td><td align=right>$political_freedom</td></tr>");
	echo ("<tr><td colspan=2><hr></td</tr>");
	echo ("<tr><td>GDP</td><td align=right>$".number_format($gdp)."</td></tr>");
	echo ("<tr><td>GDP per Capita</td><td align=right>".number_format($gdppc)."</td></tr>");
	echo ("<tr><td>Consumption</td><td align=right>".number_format($cons)."</td></tr>");
	echo ("<tr><td>Gov't Effeciency</td><td align=right>".number_format($govt_eff*100)."%</td></tr>");
	echo ("<tr><td>Gov't Budget</td><td align=right>".number_format($govt_exp)."</td></tr>");
	echo ("<tr><td>Gov't Waste</td><td align=right>".number_format($budget - ($budget * $govt_eff))."</td></tr>");
	echo ("<tr><td>Unemployment</td><td align=right>".number_format($unemployment*100)."%</td></tr>");
	echo ("<tr><td>Consumer Confidence</td><td align=right>".number_format($real_cons_conf*100)."%</td></tr>");
  echo ("<tr><td>Exchange Rage</td><td>1 CURRENCY = $".number_format($ex_rate, 2, '.', ',')." USD</td></tr>\n");
  echo ("<tr><td>&nbsp;</td><td>".number_format(1/$ex_rate, 2, '.', ',')." CURRENCY = 1 USD</td></tr>\n");
  
  
  $growth_rate  = ($economic_health*.25)+($real_cons_conf*.5);
  $growth_rate += (($civil_liberties-5)*.1);
  $growth_rate += (($economic_strength-3)*.1);
  $growth_rate -= ((rand()%750)+250)*.001;
  
  
	echo ("<tr><td colspan=2><hr></td</tr>");
	echo ("<tr><td>Growth Rate</td><td align=right>".number_format($growth_rate, 3, '.', ',')."%</td></tr>");
  
  
	echo ("</table>");

	// resources
	$resources = array();

	# us consumption = 						2,777,650,000 barrels per year, 10 barrels per person per year ??
	# south africa consumption =    176,660,000 barrels per year, 4  barrels per person per year 
	# ireland consumption = 				 64,094,000 barrels per year, 15 barrels per person per year 
	# estonian consumption =		 	    2,226,500 barrels per year
	# lesotho (pop. 2 mililon) = 	      511,000 barrels per year
	$resource{'name'} = "oil";
	$resource{'unit_value'} = 70;
	$resource{'unit_text'} = "barrel";
	#$bpp = 25*($real_cons_conf);
	#$poptot = $bpp*($population*($real_cons_conf));
	$bpp = 20*($real_cons_conf);
  $poptot = $bpp*($population*(($economic_health/10)+($economic_strength/10)+($civil_liberties/10)))/3;
	$resource{'consumption'} = $poptot;
	array_push($resources,$resource);	
	
	$resource{'name'} = "food";
	$resource{'unit_value'} = 4500;
	$resource{'unit_text'} = "pound";
	$resource{'consumption'} = (($population*(1-$unemployment))*(1+$real_cons_conf))*365;
	array_push($resources,$resource);
	
	echo "<hr>";
	echo "<table>";
	echo "<tr><td colpan=3><b>RESOURCES</b></td></tr>";
	echo "<tr><td><u>Resouce</u></td><td align=center colspan=2><u>Consumption</u></td></tr>";
	foreach ($resources as $res)
	{
		echo "<tr><td>".$res{'name'}."</td><td align=right>".number_format($res{'consumption'})." ".$res{'unit_text'}."s</td><td>per year</td></tr>";
	}
	echo "</table>";
	
	$expenditures = array();
	$expenditure{'name'} = 'administration';
	$expenditure{'budget'} = min(max(0,$_REQUEST['admin']),1);
	array_push($expenditures,$expenditure);

	$expenditure{'name'} = 'welfare';
	$expenditure{'budget'} = min(max(0,$_REQUEST['welfare']),1);
	array_push($expenditures,$expenditure);

	$expenditure{'name'} = 'healthcare';
	$expenditure{'budget'} = min(max(0,$_REQUEST['healthcare']),1);
	array_push($expenditures,$expenditure);
	
	$expenditure{'name'} = 'education';
	$expenditure{'budget'} = min(max(0,$_REQUEST['education']),1);
	array_push($expenditures,$expenditure);
	
	$expenditure{'name'} = 'religion';
	$expenditure{'budget'} = min(max(0,$_REQUEST['religion']),1);
	array_push($expenditures,$expenditure);
	
	$expenditure{'name'} = 'military';
	$expenditure{'budget'} = min(max(0,$_REQUEST['military']),1);
	array_push($expenditures,$expenditure);
	
	$expenditure{'name'} = 'law & order';
	$expenditure{'budget'} = min(max(0,$_REQUEST['lo']),1);
	array_push($expenditures,$expenditure);
	
	$expenditure{'name'} = 'environment';
	$expenditure{'budget'} = min(max(0,$_REQUEST['environment']),1);
	array_push($expenditures,$expenditure);
	
	$totexp = 0;
	echo "<hr>";
	echo "<table>";
	echo "<tr><td colpan=3><b>EXPENDITURES</b></td></tr>";
	echo "<tr><td><u>Expense</u></td><td><u>Percentage</u></td><td align=center><u>Amount</u></td></tr>";
	foreach ($expenditures as $exp)
	{
		echo "<tr><td>".$exp{'name'}."</td><td>".number_format($exp{'budget'}*100)."%</td><td align=right>$".number_format($exp{'budget'}*$govt_exp)."</td></tr>";
		$totexp += ($exp{'budget'}*$govt_exp);
	}
	
	echo "<tr><td colspan=3><hr></td></tr>";
	echo "<tr><td width=50%><b>Total Govt Spending</b></td><td align=right>$".number_format($totexp)."</td></tr>\n";
	echo "<tr><td width=50%><b>Govt Surplus/Defecit</b></td><td align=right>$".number_format($govt_exp-$totexp)."</td></tr>\n";
	echo "</table>";	
}

function eo($arg,$def)
{
	if (strlen($arg) > 0)
		return  $arg;
	else
		return $def;
}

?>
