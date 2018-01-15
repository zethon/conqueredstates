<?
//-----------------------------------------------------------------------------
// $RCSfile: createnation.php,v $ $Revision: 1.12 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/global.php');
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./nations.php');
require_once('./users.php');
require_once('./maps.php');

define('NOP_GOVSTYLE',7);
define('NOP_RELIGION',4);
define('NOP_ECONOMY',3);
define('NOP_MILITARY',4);

function genStatFromOption($option,$numoptions)
{
	$startpt = 0;
	
	if (($option/$numoptions) <= .25)
		$startpt = 6;
	else if (($option/$numoptions) <= .5)
		$startpt = 4;
	else if (($option/$numoptions) <= .75)
		$startpt = 2;
	else if (($option/$numoptions) <= 1)
		$startpt = 0.01;
	
	$endpt = $startpt + 3.5;
	return ((rand()%(($endpt-$startpt)*100))*.01)+$startpt;
}

$gAction = $_REQUEST['action'];

// is this a valid user?
if (!@isValidLobbyUser(SafeQueryData($_COOKIE[$gCookieLogin]),SafeQueryData($_COOKIE[$gCookiePassword])))
{
	header ("Location: $gLobbyURL");
	exit;
}

// does the user already exist on this game server?
$userloader = new UserLoader();
if ($userloader->loadUser(SafeQueryData($_COOKIE[$gCookieLogin])) != null)
{
	header ("Location: $gMainURL?unknownuser");
	exit;
}

// did we get a valid region id?
$regionid = @SafeQueryData($_COOKIE[$gJoiningCountry]);
if (!intval($regionid) || $regionid == "")
{
	header ("Location: $gMainURL?invalidregionid=$regionid");
	exit;
}

// is this an actual region id
$regloader = new RegionLoader();
$region = $regloader->loadRegion($regionid);
if ($region->id <= 0)
{
	header ("Location: $gMainURL?unknownregion=$regionid");
	exit;
}

if ($gAction == "create")
{
	$errorstr = "";
	$expiretime = time()+10;
	
	// is this region already claimed
	$nloader = new NationLoader();
	$tempnation = $nloader->getNationByRegionID($regionid);
	if ($tempnation->id > 0)
	{
		header ("Location: $gMainURL?alreadyclaimed=$regionid");
		exit;
	}
	
	// get the form info	
	$nationname = @SafeQueryData($_REQUEST['nationname']);
	$currency = strtolower(@SafeQueryData($_REQUEST['currency']));
	$motto = @SafeQueryData($_REQUEST['motto']);
	$taxrate = @SafeQueryData($_REQUEST['taxrate']);
	$titleid = @SafeQueryData($_REQUEST['title']);
	$flagid = substr($_REQUEST['flag'],0,strpos($_REQUEST['flag'],";"));

	if ($flagid == 0)
		$errorstr .= "H";
	
	// expenses
	$exeducation = @SafeQueryData($_REQUEST['exeducation']);
	$exhealthcare = @SafeQueryData($_REQUEST['exhealthcare']);
	$exmilitary = @SafeQueryData($_REQUEST['exmilitary']);
	$exreligion = @SafeQueryData($_REQUEST['exreligion']);
	$exenvironment = @SafeQueryData($_REQUEST['exenvironment']);
	$exlawandorder = @SafeQueryData($_REQUEST['exlawandorder']);
	$pertotal = $exeducation + $exhealthcare + $exmilitary + $exreligion + $exenvironment + $exlawandorder;
	
	if ($exeducation > 100 || $exhealthcare > 100 || $exmilitary > 100 || 
			$exreligion > 100 || $exenvironment > 100 || $exlawandorder > 100 || 
			$exeducation < 0 || $exhealthcare < 0 || $exmilitary < 0 || 
			$exreligion < 0 || $exenvironment < 0 || $exlawandorder < 0 || 
			strlen($exeducation) == 0 || strlen($exhealthcare) == 0  || strlen($exmilitary) == 0 || 
			strlen($exreligion) == 0  || strlen($exenvironment) == 0  || strlen($exlawandorder) == 0)
	{
		$errorstr .= "#"; // generic error that gets picked up via teh cookies
	}
	else if ($pertotal > 100)
		$errorstr .= "F";
	else if ($pertotal < 0)
		$errorstr .= "G";	
	
	// dropdowns
	$govstyle = @SafeQueryData($_REQUEST['govstyle']);
	if ($govstyle > NOP_GOVSTYLE) $govstyle = NOP_GOVSTYLE; else if ($govstyle < 0) $govstyle = 0;
	$religion = @SafeQueryData($_REQUEST['religion']);
	if ($religion > 4) $religion = 4; else if ($religion < 0) $religion = 0;
	$economy = @SafeQueryData($_REQUEST['economy']);
	if ($economy > 4) $economy = 4; else if ($economy < 0) $economy = 0;
	$military = @SafeQueryData($_REQUEST['military']);
	if ($military > 4) $military = 4; else if ($military < 0) $military = 0;
	
	if (strlen($nationname) < 3)
		$errorstr .= "A";
	else
	{
		$query = "SELECT DISTINCT * FROM nation WHERE (name = '$nationname')";
		$datatable = $DBObj->query($query);
		
		if ($DBObj->num_rows($datatable) > 0)
			$errorstr .= "B";
	}
	
	if (strlen($currency) == 0)
		$errorstr .= "C";
		
	if (strlen($motto) == 0)
		$errorstr .= "D";
		
	if (!is_numeric($taxrate))
		$errorstr .= "E";
	else
	{
			$temp = intval($taxrate);
			if ($temp < 0 || $temp > 100)
				$errorstr .= "E";
	}
	
	if (strlen($errorstr) > 0)
	{
		setcookie("temp_nation_name",$nationname,$expiretime);
		setcookie("temp_currency",$currency,$expiretime);
		setcookie("temp_motto",$motto,$expiretime);
		setcookie("temp_taxrate",$taxrate,$expiretime);
		setcookie("temp_titleid",$titleid,$expiretime);
		setcookie("temp_govstyle",$govstyle,$expiretime);
		setcookie("temp_religion",$religion,$expiretime);
		setcookie("temp_economy",$economy,$expiretime);
		setcookie("temp_military",$military,$expiretime);
		setcookie("temp_flagid",$flagid,$expiretime);
		
		setcookie("temp_exeducation",$exeducation,$expiretime);
		setcookie("temp_exhealthcare",$exhealthcare,$expiretime);
		setcookie("temp_exmilitary",$exmilitary,$expiretime);
		setcookie("temp_exreligion",$exreligion,$expiretime);
		setcookie("temp_exenvironment",$exenvironment,$expiretime);
		setcookie("temp_exlawandorder",$exlawandorder,$expiretime);
		
		setcookie("nation_errorstr",$errorstr,$expiretime); 
		header("Location: createnation.php");
		exit;
	}

	// build the nation object
	$nation = new Nation();
	$nation->name = $nationname;
	$nation->motto = $motto;
	$nation->currency = $currency;
	$nation->taxrate = intval($taxrate) * .01;
	$nation->titleid = $titleid ? $titleid : 1;
	$nation->flagid = $flagid;
	$nation->pushRegion($region); 

	// generate the national stats
	$govstat = genStatFromOption($govstyle,NOP_GOVSTYLE);
	$relstat = genStatFromOption($religion,NOP_RELIGION);
	$ecostat = genStatFromOption($economy,NOP_ECONOMY);
	$milstat = genStatFromOption($military,NOP_MILITARY);
	
	// set the nation stats
	$stats = new NationStats();
	$stats->economic_strength = (.01*(rand()%200))+.5;
	$stats->economic_health = ($govstat*.3) + ($relstat * .1) + ($ecostat * .4) + ($milstat * .2);
	$stats->civil_liberties = ($govstat*.3) + ($relstat * .3) + ($ecostat * .1) + ($milstat * .3);
	$stats->political_freedom = ($govstat*.75) + ($relstat * .1) + ($ecostat * .05) + ($milstat * .15);
	$nation->stats = $stats;	
	
	$expn = new NationExpenses();
	$expn->education = $exeducation * .01;
	$expn->healthcare = $exhealthcare * .01;
	$expn->military = $exmilitary * .01;
	$expn->religion = $exreligion * .01;
	$expn->environment = $exenvironment * .01;
	$expn->lawandorder = $exlawandorder * .01;
	$nation->expenses = $expn;	
	
	$nwriter = new NationWriter();
	$nationid = $nwriter->createNation($nation);
	if ($nationid)
	{
		$userobj = new User();
		$userobj->username = SafeQueryData($_COOKIE[$gCookieLogin]);
		$userobj->password = SafeQueryData($_COOKIE[$gCookiePassword]);
		$userobj->countryid = $nationid;
		
		$userw = new UserWriter();
		if ($userw->addUser($userobj))
		{
			// SUCCESS! Get the hell outta here...

			// create some maps 
			$query = "SELECT regions.region_id, name, region_coords.coords,nation_regions.nation_id FROM regions LEFT JOIN region_coords USING (region_id) LEFT JOIN nation_regions USING (region_id)WHERE (region_coords.region_id > 0) AND (nation_id IS NULL) ORDER BY region_id";
			@createWorldRegionMap("$gImagesDir/$gFreeRegionWorldMap",true,$query);
			@createWorldNationMap(true); // refresh the nation map

			header("Location: $gMainURL/?id=$nationid");
			exit;
		}
		else // couldn't create the user
		{
			// so delet the country we just made
			$nwriter->deleteNation($nation->id);
		}
		
	}
}

// we're going to display the screen, so setup the functions
function printTitleDropDown($selid = -1)
{
	$DBObj = $GLOBALS['DBObj'];	
		
	$query = "SELECT * FROM nation_titles ORDER BY nation_title_text";
	$datatable = $DBObj->query($query);
	
	echo "<select name='title'>\n";
	while ($storeitem = $DBObj->fetch_array($datatable))
	{
		$selected = "";
		$title = $storeitem["nation_title_text"];
		$id = $storeitem["nation_title_id"];
		
		if ($selid == $id)
			$selected = "selected=true";
			
		echo "<option $selected value=$id>$title</option>\n";
		
	}
	echo "</select>\n";
}

	// turn the string into a hash for easire acess
	$errorstr = $_COOKIE['nation_errorstr'];
	for($i=0;$i<strlen($errorstr);$i++)
		$errorHash{$errorstr{$i}} = true;
		
	$gTitleID = $_COOKIE['temp_titleid'];
?>

<!-- action [<? echo $gAction; ?>] error [<? echo $errorstr; ?>] temp [<? echo $gTitleID; ?>]-->
<html>
<head>
</head>

<link rel="stylesheet" href="css/main.css" type="text/css">
<script type="text/javascript">
<!--
function updateFlag(obj)
{ 
	var val =  obj.options[obj.selectedIndex].value;
	var imgurl = "<? echo $gImagesURL; ?>/flags/"+val.substring(val.indexOf(';')+1);
  document.flagimg.src = imgurl;
}
-->
</script>

<body onLoad='updateFlag(document.mainform.flag);'>
<center>
<hr width=800><font class=banner_text>Conquered States</font><hr width=800>
<table width=800 cellpadding=0 cellspacing=0>
<tr valign=top><td align=right class=tiny_text><b>Server</b>: <i><? echo $gGameServerName; ?></td><tr>
</table>
<a href="viewregion.php"  class=menuitem target=_new>View Regions</a>&nbsp;|&nbsp;
<a href="viewnation.php" class=menuitem target=_new>View Nations</a>&nbsp;|&nbsp;
</center>

<br>


<center><h1>Customize Your Nation</h1></center>

<table align=center bgcolor=#000000 cellspacing=1 cellpadding=0><tr><td>
<table cellspacing=0 cellpadding=1 bgcolor=DEE7F7>
<tr><td colspan=2 align=center><img border=1 src="<? echo "viewmap.php?action=viewregion&id=".$region->id; ?>" name="">
<tr class=gridbg1><td>Region Name:</td><td align=right><? echo $region->name; ?></td><tr>
<tr class=gridbg1><td>Region Population:</td><td align=right><? echo number_format($region->population); ?></td><tr>
</table>
</td></tr></table>

<hr width=800>

<form method=post action=createnation.php name=mainform>
<input type=hidden name=action value=create>
<table align=center>
<tr class=gridbg1><td><b>Nation Name:</b></td><td>The <? printTitleDropDown($gTitleID); ?> of <input type=text name=nationname maxlength=20 value='<? echo $_COOKIE["temp_nation_name"]; ?>'>
<? if ($errorHash{'A'}) { ?>
	<font class=error_text>Invalid Nation Name</font>
<? } 	else if ($errorHash{'B'}) { ?>
	<font class=error_text>Nation Name Already Exists</font>
<? } ?>
</td></tr>

<tr class=gridbg1><td valign=top><b>National Flag:</b></td><td valign=top><? printFlagDropDown($_COOKIE['temp_flagid'],"flag","onChange='updateFlag(this);'"); ?><br><br><img name="flagimg" src=""></td></tr>
	
<tr class=gridbg1><td><b>Currency Name:</b></td><td><input type=text name=currency maxlength=20 value='<? echo $_COOKIE["temp_currency"]; ?>'>
<? if ($errorHash{'C'}) { ?>
	<font class=error_text>Your citizens must know what to call their unit of currency!</font>
<? } ?>
</td></tr>

<tr class=gridbg1><td><b>National Motto:</b></td><td><input type=text name=motto maxlength=75 value='<? echo $_COOKIE["temp_motto"]; ?>'>
<? if ($errorHash{'D'}) { ?>
	&nbsp;<font class=error_text>The people of your nation demand a motto!</font>
<? } ?>
</td></tr>

<tr><td colspan=2><hr></td></tr>

<tr class=gridbg1><td><b>National Tax Rate:</b></td><td><input style="width:35px" type=text name=taxrate maxlength=3 width=5 value='<? echo $_COOKIE["temp_taxrate"]; ?>'>%
<? if ($errorHash{'E'}) { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>
</td></tr>

<tr><td class=gridbg1><b>Government Type:</b></td>
<td>
<select name=govstyle>
<option <? if ($_COOKIE["temp_govstyle"] == "1") echo "selected=true"; ?> value=1>Direct Democracy</option>
<option <? if ($_COOKIE["temp_govstyle"] == "2") echo "selected=true"; ?> value=2>Confederacy</option>
<option <? if ($_COOKIE["temp_govstyle"] == "3") echo "selected=true"; ?> value=3>Republic</option>
<option <? if ($_COOKIE["temp_govstyle"] == "4") echo "selected=true"; ?> value=4>Socialist</option>
<option <? if ($_COOKIE["temp_govstyle"] == "5") echo "selected=true"; ?> value=5>Communist</option>
<option <? if ($_COOKIE["temp_govstyle"] == "6") echo "selected=true"; ?> value=6>Dictatorship</option>
<option <? if ($_COOKIE["temp_govstyle"] == "7") echo "selected=true"; ?> value=7>Feudal System</option>
</select>
</td>
</tr>

<tr><td class=gridbg1><b>Religious Freedom:</b></td>
<td>
<select name=religion>
<option <? if ($_COOKIE["temp_religion"] == "1") echo "selected=true"; ?> value=1>Freedom of Religion</option>
<option <? if ($_COOKIE["temp_religion"] == "2") echo "selected=true"; ?> value=2>State Sponsored Religion</option>
<option <? if ($_COOKIE["temp_religion"] == "3") echo "selected=true"; ?> value=3>Mandatory State Religion</option>
<option <? if ($_COOKIE["temp_religion"] == "4") echo "selected=true"; ?> value=4>Outlawed Religion</option>
</select>
</td>
</tr>

<tr><td class=gridbg1><b>Economy:</b></td>
<td>
<select name=economy>
<option <? if ($_COOKIE["temp_economy"] == "1") echo "selected=true"; ?> value=1>Freemarket Paradise</option>
<option <? if ($_COOKIE["temp_economy"] == "2") echo "selected=true"; ?> value=2>Regulated Business</option>
<option <? if ($_COOKIE["temp_economy"] == "3") echo "selected=true"; ?> value=3>No Freemarket</option>
</select>
</td>
</tr>

<tr><td class=gridbg1><b>Military Service:</b></td>
<td>
<select name=military>
<option <? if ($_COOKIE["temp_military"] == "1") echo "selected=true"; ?> value=1>Volunteer</option>
<option <? if ($_COOKIE["temp_military"] == "2") echo "selected=true"; ?> value=2>Mandatory 1 year tour of duty</option>
<option <? if ($_COOKIE["temp_military"] == "3") echo "selected=true"; ?> value=3>Mandatory 5 year tour of duty</option>
<option <? if ($_COOKIE["temp_military"] == "4") echo "selected=true"; ?> value=4>Mandatory lifetime commitment</option>
</select>
</td>
</tr>

<tr><td colspan=2><hr></td></tr>
<tr><td colspan=2 class=tiny_text>Enter perecentage of gov't budget you wish to spend on each governmental department</td></tr>

<? if ($errorHash{'F'}) { ?>
	<tr><td colspan=2><font class=error_text>Totals of percents must be less than 100</font></td></tr>
<? } ?>

<? if ($errorHash{'G'}) { ?>
	<tr><td colspan=2><font class=error_text>Totals of percents must be greater than 0</font></td></tr>
<? } ?>

<?
	// TODO:: TTURN THE EXPENSE TEXT BOXES INTO A FOREACH LOOP
?>

<tr class=gridbg1>
	<td><b>Education:</b></td>
	<td>
		<input style="width:35px" type=text name=exeducation maxlength=3 width=5 value='<? echo $_COOKIE["temp_exeducation"]; ?>'>%
<? if ($_COOKIE['temp_exeducation'] < 0 || $_COOKIE['temp_exeducation'] > 100 || strlen($_COOKIE['temp_exeducation']) == 0) 
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>	
	</td>
</tr>

<tr class=gridbg1>
	<td><b>Healthcare:</b></td>
	<td>
		<input style="width:35px" type=text name=exhealthcare maxlength=3 width=5 value='<? echo $_COOKIE["temp_exhealthcare"]; ?>'>%
<? if ($_COOKIE['temp_exhealthcare'] < 0 || $_COOKIE['temp_exhealthcare'] > 100 || strlen($_COOKIE['temp_exhealthcare']) == 0)  
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>			
	</td>
</tr>

<tr class=gridbg1>
	<td><b>Military:</b></td>
	<td><input style="width:35px" type=text name=exmilitary maxlength=3 width=5 value='<? echo $_COOKIE["temp_exmilitary"]; ?>'>%
<? if ($_COOKIE['temp_exmilitary'] < 0 || $_COOKIE['temp_exmilitary'] > 100 || strlen($_COOKIE['temp_exmilitary']) == 0) 
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>			
</td></tr>

<tr class=gridbg1>
	<td><b>Religion:</b></td>
	<td><input style="width:35px" type=text name=exreligion maxlength=3 width=5 value='<? echo $_COOKIE["temp_exreligion"]; ?>'>%
<? if ($_COOKIE['temp_exreligion'] < 0 || $_COOKIE['temp_exreligion'] > 100 || strlen($_COOKIE['temp_exreligion']) == 0)  
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>		
</td></tr>

<tr class=gridbg1>
	<td><b>Environment:</b></td>
	<td><input style="width:35px" type=text name=exenvironment maxlength=3 width=5 value='<? echo $_COOKIE["temp_exenvironment"]; ?>'>%
<? if ($_COOKIE['temp_exenvironment'] < 0 || $_COOKIE['temp_exenvironment'] > 100 || strlen($_COOKIE['temp_exenvironment']) == 0) 
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>		
</td></tr>

<tr class=gridbg1>
	<td><b>Law & Order:</b></td>
	<td><input style="width:35px" type=text name=exlawandorder maxlength=3 width=5 value='<? echo $_COOKIE["temp_exlawandorder"]; ?>'>%
<? if ($_COOKIE['temp_exlawandorder'] < 0 || $_COOKIE['temp_exlawandorder'] > 100 || strlen($_COOKIE['temp_exlawandorder']) == 0)  
			if ($_COOKIE["nation_errorstr"] != "") { ?>
	&nbsp;<font class=error_text>Enter a number 0 thru 100</font>
<? } ?>			
</td></tr>

<tr><td colspan=2>&nbsp;</td></tr>
<tr><td colspan=2><input type=submit value="Create Nation"></td></tr>
</table>
</form>

</body>
</html>

