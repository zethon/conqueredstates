<? 
//-----------------------------------------------------------------------------
// $RCSfile: nations.php,v $ $Revision: 1.28 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');
require_once('./regions.php');
require_once('./resources.php');

// GLOBALS
$gExpenses = array("education" => "Education","healthcare" => "Healthcare","military" => "Military", "religion" => "Religion", "environment" => "Environment", "lawandorder" => "Law and Order"); 

class NationExpenses
{
	var $nation_expense_id;
	var $education = 0;
	var $healthcare = 0;
	var $military = 0;
	var $religion = 0;
	var $environment = 0;
	var $lawandorder = 0;

	function setProp($PropName, $PropValue) 
	{
		$this->$PropName = $PropValue;
	}	
	
	function ModifyExpense($expname,$val)
	{
		if (!isset($this->$expname))
			return false;
			
		$oldval = $this->$expname;
		$this->$expname += $val;
		
		if ($this->$expname < 0)
			$this->$expname = 0;
			
		return (($this->$expname - $oldval) != 0);
	}
	
	function Save()
	{
		$DBObj  = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE nation_expenses SET education='$this->education',healthcare='$this->healthcare',military='$this->military',religion='$this->religion',environment='$this->environment',lawandorder='$this->lawandorder' WHERE (nation_expense_id = '$this->nation_expense_id')");		
	}
}

class NationStats
{
	var $statsid = 0;
	var $economic_strength = 0;
	var $economic_health = 0;
	var $civil_liberties = 0;
	var $political_freedom = 0;	
	
	function ModifyStat($statname,$val)
	{
		if (!isset($this->$statname))
			return false;
			
		$oldval = $this->$statname;
		$this->$statname += $val;
		
		if ($this->$statname <= 0)
			$this->$statname = 0.001;
		else if ($this->$statname >= 10)
			$this->$statname = 9.999;

		return (($this->$statname - $oldval) != 0);
	}
	
	function Save()
	{
		$DBObj  = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE nation_stats SET economic_strength='$this->economic_strength',economic_health='$this->economic_health',civil_liberties='$this->civil_liberties',political_freedom='$this->political_freedom' WHERE (nation_stats_id = '$this->statsid')");		
	}
}

class EconomicStats
{
	var $gdp = 0;
	var $gdppc = 0;
	var $consumption = 0;
	var $govteff = 0;
	var $govtbudg = 0;
	var $govtwaste = 0;
	var $unemployment = 0;
	var $consconf = 0; // $real_cons_conf
	var $exchrate = 0;	
}

class Nation
{
	var $id;
	var $name;
	var $motto;
	var $currency;
	var $taxrate;
	var $balance;
	
	var $flagid;
	var $customflag;

	var $title;
	var $titleid;

	var $currency_id;
	var $currency_symbol;
	
	var $resources = array();
	var $regions = array();
	var $stats = null; 							//NationStats
	var $ecstats = null;						//EconomicStats
	var $expenses = null;						//NationExpenses
	
	// oneday
	var $expense_names = array('education','healthcare','military','religion','environment','lawandorder');
	
	function pushRegion($region)
	{
		array_push($this->regions,$region);
	}
	
   function setProp($PropName, $PropValue) 
   {
       $this->$PropName = $PropValue;
   }		
   
	function GetPopulation()
	{
		$retval = 0;
		
		foreach ($this->regions as $region)
		{
			$retval += $region->population;
		}
		
		return $retval;	
	}
	
	function GetGrowthRate()
	{
		if ($this->stats == null)
			return 0;
			
		if ($this->ecstats == null)
			$this->CalcEconomicStats();
			
		$growth_rate  = ($this->stats->economic_health*.25)+($this->ecstats->consconf*.5);
		$growth_rate += (($this->stats->civil_liberties-2)*.1);
		$growth_rate += (($this->stats->economic_strength-1.5)*.1);
		$growth_rate -= ((rand()%250)+750)*.001;
		$growth_rate *= .01;
  	
		return $growth_rate;		
	}
	
	function CalcEconomicStats()
	{
		if ($this->stats == null)
			return;

		$tax = $this->taxrate;					
		$population = $this->GetPopulation();				
			
		$this->ecstats = new EconomicStats();
		
		// **************** BEGIN PASTE FROM LN.PHP **********
		
		# economy measurement (100 thru 35000) Good = 7500
		$prod = 35000*($this->stats->economic_strength*.1);
		
		# civil rights measurement (-0.1 thru 0.05) Average = 0
		$con_conf_civil = (($this->stats->civil_liberties*(15/10))-10)*.01;		
		
		# economy measurement (-0.1 thru 0.05) Good = 0
		$con_conf_econ = (($this->stats->economic_health*(15/10))-10)*.01;		
		
		# consumer confidence (+1?)
		$con_conf = 1 + $con_conf_civil + $con_conf_econ;		

		# gov't effeciency (based on political freedom?) (-0.08 thru -0.1) Average = -0.04
		$govt_eff = 1 + ((($this->stats->political_freedom*(15/10))-15.1)*.01);		
		
		# calculate work enthusiasm! 
		$work_enth_tax = array(0 =>0.02,1 =>0,2 =>-0.02,3 =>-0.04,4 =>-0.06,5 =>-0.08,6 =>-0.1,7 =>-0.12,8 =>-0.14,9 =>-0.16,10 =>-0.18);
		
		# worker enthusiasm (economically) (-0.07 thru 0.03) "Good" = 0
		$work_enth_econ = (($this->stats->economic_health*(11/10))-7)*.01;
		
		# worker enthusiasm (civil rights) -0.1 thru 0.05, "Average" = 0
		$work_enth_civil = (($this->stats->civil_liberties*(15/10))-10)*.01;		
		
		# worker enthusiasm (politica freedoms) -0.04 thru 0.01, "Good" = 0
		$work_enth_pol = (($this->stats->political_freedom*(6/10))-4)*.01;	
		
		# calculations
		$work_enth = 1 + $work_enth_tax[ceil($tax * 10)] + $work_enth_econ + $work_enth_civil + $work_enth_pol;		
		
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
		// *************** END PASTE FROM LN.PHP *************
		
		$this->ecstats->gdp = $gdp;
		$this->ecstats->gdppc = $gdp/$population;
		$this->ecstats->consumption = $cons;
		$this->ecstats->govteff = $govt_eff;
		$this->ecstats->govtbudg = $govt_exp;
		$this->ecstats->govtwaste = $budget - ($budget * $govt_eff);
		$this->ecstats->unemployment = $unemployment;
		$this->ecstats->consconf = $real_cons_conf;
		$this->ecstats->exchrate = $ex_rate;		
	}	
}

class NationWriter
{
	function saveNation($data,$natobj,$saveregions = false)
	{
		$DBObj  = $GLOBALS['DBObj'];
		$total = count($data);
		$datastr = "";

		// make sure we have an even array
		if ($total % 2 == 0)
		{
			$idx = 0;
			while ($idx < $total)
			{
				$column = $data[$idx];
				$string = $data[$idx+1];
				
				if (strlen($datastr) > 0)
					$datastr .= ",";
					
				$datastr .= "$column='$string'";
				
				$idx += 2;
			}
			
			$query = "UPDATE nation SET $datastr WHERE (nation_id = '$natobj->id')";
			$DBObj->query($query);
		}
		// TODO: ADD WARNING HERE IN THE ELSE
		
		if ($saveregions)
		{
			$regWriter = new RegionWriter();
			foreach ($natobj->regions as $region)
			{
				$regWriter->saveRegion($region);
			}
		}		
	}
	
	function deleteNation($id)
	{
		$DBObj = $GLOBALS['DBObj'];		
		$retval = 0;
			
		$DBObj->query("DELETE FROM nation WHERE (nation_id = '$id')");
		$retval += $DBObj->affected_rows();
		
		$DBObj->query("DELETE FROM nation_regions WHERE (nation_id = '$id')");
		$retval += $DBObj->affected_rows();
		
		$DBObj->query("DELETE FROM nation_stats WHERE (nation_id = '$id')");
		$retval += $DBObj->affected_rows();
		
		$DBObj->query("DELETE FROM users WHERE (country_id = '$id')");
		$retval += $DBObj->affected_rows();

		$DBObj->query("DELETE FROM nation_expenses WHERE (nation_id = '$id')");
		$retval += $DBObj->affected_rows();
		
		return $retval;					
	}
	
	function createNation($natobj)
	{
		$DBObj = $GLOBALS['DBObj'];		
		
		$dbhost = $GLOBALS['dbhost'];
		$dbname = $GLOBALS['dbname'];
		$dbuser = $GLOBALS['dbuser'];
		$dbpw   = $GLOBALS['dbpw'];		
		
		$DBObj = $GLOBALS['DBObj'];
		
		// insert this into the nation table
		$query = "INSERT INTO nation (name,tax,motto,currency,nation_title,flag) VALUES ('".$natobj->name."','".$natobj->taxrate."','".$natobj->motto."','".$natobj->currency."','".$natobj->titleid."','".$natobj->flagid."')";
		$DBObj->query($query);
		
		// get the nation id and set it
		$id = $natobj->id = $DBObj->insert_id();
		
		// insert this nation into the region table
		$region = $natobj->regions[0];
		$DBObj->query("INSERT INTO nation_regions (nation_id,region_id) VALUES ('".$natobj->id."','".$region->id."')");
		
		// insert the nation stats
		$query =  "INSERT INTO nation_stats (nation_id,economic_strength,economic_health,civil_liberties,political_freedom) ";
		$query .= "VALUES ('".$natobj->id."','".$natobj->stats->economic_strength."','".$natobj->stats->economic_health."','".$natobj->stats->civil_liberties."','".$natobj->stats->political_freedom."')";
		$DBObj->query($query);
		
		// national expenses
		$query = "INSERT INTO nation_expenses (nation_id,education,healthcare,military,religion,environment,lawandorder) ";
		$query .= "VALUES ('".$natobj->id."','".$natobj->expenses->education."','".$natobj->expenses->healthcare."','".$natobj->expenses->military."','".$natobj->expenses->religion."','".$natobj->expenses->environment."','".$natobj->expenses->lawandorder."')";
		$DBObj->query($query);
		
		// create the resource name array
		//$resArray = array();
		$datatable = $DBObj->query("SELECT * FROM resources");
		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			$DBObj->query("INSERT INTO nation_resource_inventory (nation_id,resource_id) VALUES ('$natobj->id','".($storeitem['resource_id'])."')");
//			$resobj = new Resource();
//			$resobj->resource_id = $storeitem['resource_id'];
//			$resobj->name = $storeitem['resource_name'];
//			$resobj->unit = $storeitem['resource_unit'];
//			$resobj->producername = $storeitem['producer_name'];
			
			
			//$resArray[$storeitem['resource_id']] = $resobj;
		}
	
		

		return $id;
	}
}

class NationLoader
{
	function getNationByRegionID($id)
	{
		$DBObj = $GLOBALS['DBObj'];

		$data = $DBObj->query_first("SELECT DISTINCT nation_id FROM nation_regions WHERE (region_id = $id)");
		$nationid = $data["nation_id"];
		
		return $this->loadNation($nationid);		
	}
	
	
	function loadNationWithStats($id,$loadregs = true)
	{
		$retobj = $this->loadNation($id,$loadregs);
		$retobj->resources = $this->getNationResources($id);
		return $retobj;
	}
	
	function getNationResources($id)
	{
		$DBObj = $GLOBALS['DBObj'];
		$retarray = array();
		$data = $DBObj->query("SELECT * FROM nation_resource_inventory LEFT JOIN resources USING (resource_id) WHERE (nation_id = $id) ORDER BY resources.resource_id");
		
		while ($storeitem = $DBObj->fetch_array($data))
		{
			$resobj = new NationResource();
			$resobj->quantity_id = $storeitem["nation_resource_inventory_id"];
			$resobj->quantity = $storeitem["quantity"];
			$resobj->resource_id = $storeitem["resource_id"];
			$resobj->name = $storeitem["resource_name"];
			$resobj->unit = $storeitem["resource_unit"];
			$resobj->producername = $storeitem["producer_name"];
			
			$retarray[$storeitem["resource_id"]] = 	$resobj;
		}		
		return $retarray;
	}
		
	function getNationExpensesObj($data)
	{
		$tempnation = new Nation();
		$retobj = new NationExpenses();
		
		foreach ($tempnation->expense_names as $name)
		{
			$retobj->$name = $data["$name"];
		}
		
		$retobj->nation_expense_id = $data["nation_expense_id"];

		return $retobj;				
	}
	
	function getNationStatsObj($data)
	{
		$stats = new NationStats();
		$stats->statsid = $data["nation_stats_id"];
		$stats->economic_strength = $data["economic_strength"];
		$stats->economic_health = $data["economic_health"];
		$stats->civil_liberties = $data["civil_liberties"];
		$stats->political_freedom = $data["political_freedom"];		
		
		if ($stats->economic_strength > 10) $stats->economic_strength = 10;
		if ($stats->economic_health > 10) $stats->economic_health = 10;
		if ($stats->civil_liberties > 10) $stats->civil_liberties = 10;
		if ($stats->political_freedom > 10) $stats->political_freedom = 10;

		if ($stats->economic_strength < 0) $stats->economic_strength = 0;
		if ($stats->economic_health < 0) $stats->economic_health = 0;
		if ($stats->civil_liberties < 0) $stats->civil_liberties = 0;
		if ($stats->political_freedom < 0) $stats->political_freedom = 0;
		
		return $stats;		
	}
	
	function loadNation($id,$loadregs = true)
	{
		$DBObj = $GLOBALS['DBObj'];		
		$regloader = new RegionLoader();		
		$retobj = new Nation();
		$retobj->id = -1;

		$query = <<<QUERY
SELECT nation.*, nation_stats.*, nation_expenses.*, region_id,nation_titles.nation_title_text,nation_titles.nation_title_id,nation_flags.type,currency_symbol
FROM nation 
LEFT JOIN nation_regions USING (nation_id) 
LEFT JOIN nation_stats USING (nation_id)
LEFT JOIN nation_expenses USING (nation_id)
LEFT JOIN nation_titles ON (nation_titles.nation_title_id = nation.nation_title) 
LEFT JOIN nation_flags ON (nation.flag = nation_flags.flagid) 
LEFT JOIN currency_symbols ON (nation.currency_id = currency_symbols.currency_id)
WHERE (nation.nation_id = $id)	
QUERY;

		$datatable = $DBObj->query($query);

		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			if ($retobj->id == -1)
			{
				$retobj->id = $storeitem["nation_id"];
				$retobj->name = $storeitem["name"];
				$retobj->motto = $storeitem["motto"];
				$retobj->currency = $storeitem["currency"];
				$retobj->taxrate = $storeitem["tax"];
				$retobj->title = $storeitem["nation_title_text"];
				$retobj->titleid = $storeitem["nation_title_id"];
				$retobj->balance = $storeitem["balance"];
				$retobj->flagid = $storeitem["flag"];
				$retobj->customflag = ($storeitem['type'] == 1);	
				
				$retobj->currency_id = $storeitem["currency_id"];
				$retobj->currency_symbol = $storeitem["currency_symbol"];
				
				// get member objects
				$retobj->stats = $this->getNationStatsObj($storeitem);
				$retobj->expenses = $this->getNationExpensesObj($storeitem);
			}
			
			if ($loadregs)
			{
				$regionid = $storeitem["region_id"];
				$region = $regloader->loadRegion($regionid,true);
				
				if ($region->id > 0)
				{
					$retobj->pushRegion($region);
				}
			}
			else if ($retobj->id != -1)
				break;
		}
		
		if ($retobj->id == -1)
			$retobj = null;

		return $retobj;		
	}
}

return;
?>