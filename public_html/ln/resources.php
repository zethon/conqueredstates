<? 
//-----------------------------------------------------------------------------
// $RCSfile: resources.php,v $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');
require_once('./nations.php');
require_once('./regions.php');

class Resource
{
	var $resource_id = 0;
	var $name;
	var $unit;
	var $producername;
}

class RegionResource
{
	var $region_resource_id = 0;
	var $region_id = 0;
	var $resource_id = 0;
	
	var $producerlimit;
	var $producercount;
	var $producercost;
	
	var $resourcecapacity;
	var $resourceextracted;
	var $productionrate;	// units per day
	
	function setProp($PropName, $PropValue) 
	{
	   $this->$PropName = $PropValue;
	}		
	
	function Save()
	{
		$DBObj  = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE region_resources SET producer_limit='$this->producerlimit',producer_count='$this->producercount',producer_cost='$this->producercost',resource_capacity='$this->resourcecapacity',resource_extracted='$this->resourceextracted',production_rate='$this->productionrate' WHERE (region_resource_id = '$this->region_resource_id')");
		//print ("UPDATE region_resources SET producer_limit='$this->producerlimit',producer_count='$this->producercount',producer_cost='$this->producercost',resource_capacity='$this->resourcecapacity',resource_extracted='$this->resourceextracted',production_rate='$this->productionrate' WHERE (region_resource_id = '$this->region_resource_id')\n");
	}
}

class NationResource
{
	var $quantity_id = 0;
	var $resource_id = 0;
	var $quantity = 0;
	
	var $name;
	var $unit;
	var $producername;
	
	function setProp($PropName, $PropValue) 
	{
	   $this->$PropName = $PropValue;
	}	
	
	function Save($nation = null)
	{
		if ($nation == null)
			return false;
		
		$DBObj  = $GLOBALS['DBObj'];
		
		$data = $DBObj->query_first("SELECT * FROM nation_resource_inventory WHERE (nation_id = '$nation->id' AND resource_id = '$this->resource_id')");
		//print ("SELECT * FROM nation_resource_inventory WHERE (nation_resource_inventory_id = $this->quantity_id)");
				
		if ($data["nation_id"] > 0)
		{
			// update
			$DBObj->query("UPDATE nation_resource_inventory SET quantity='$this->quantity' WHERE (nation_resource_inventory_id = '$this->quantity_id')");
			//print ("UPDATE nation_resource_inventory SET quantity='$this->quantity' WHERE (nation_resource_inventory_id = '$this->quantity_id')");
		}
		else if ($nation != null) 
		{
//			
//			// insert	if we pass a nation object and the record isn't there
//			$DBObj->query("INSERT INTO nation_resource_inventory (nation_id,resource_id,quantity) VALUE ('$nation->id','$this->resource_id','$quantity')");
			print("INSERT INTO nation_resource_inventory (nation_id,resource_id,quantity) VALUE ('$nation->id','$this->resource_id','$quantity')");

		}
	}
}


class ResConsumptionCalc
{
	var $nation = null;
	
   function setProp($PropName, $PropValue) 
   {
       $this->$PropName = $PropValue;
   }	
	
	// returns YEARLY consumption of a given resource
	function CalculateConsumption($resource_id)
	{
		$retval  = 0;
		
		if ($this->nation != null)
		{
			$nation = $this->nation;
			
			if ($nation->ecstats == null)
				$nation->CalcEconomicStats();
	
			switch ($resource_id)
			{
				case "1": // oil
					$bpp = 20*($nation->ecstats->consconf);
  				$retval = floor($bpp*($nation->GetPopulation()*(($nation->stats->economic_health/10)+($nation->stats->economic_strength/10)+($nation->stats->civil_liberties/10)))/3);
				break;
				
				case "2": // food, pounds per day consumed
					$retval = floor((($nation->GetPopulation()*(1-$nation->ecstats->unemployment))*(1+$nation->ecstats->consconf))*365);
				break;
			}
		}
		
		return $retval;
	}
	
}

?>