<? 
//-----------------------------------------------------------------------------
// $RCSfile: regions.php,v $ $Revision: 1.12 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');
require_once('./resources.php');

class Region
{
	var $id;
	var $name;
	var $coordsArray = array();
	var $coordsIDArray = array();
	var $population;	
	var $resources = array();
	
	function pushCoords($coords)
	{
		array_push($this->coordsArray,$coords);
	}
	
	function pushResource($resource)
	{
		array_push($this->resources,$resource);
	}
	
   function setProp($PropName, $PropValue) {
       $this->$PropName = $PropValue;
   }	
}

class RegionWriter
{
	function saveRegion($regobj)
	{
		$DBObj  = $GLOBALS['DBObj'];
		
		$query = "UPDATE regions SET population='$regobj->population' WHERE (region_id = '$regobj->id')";
		$DBObj->query($query);
	}
}


class RegionLoader
{
	
	function getResourceArray($id)
	{
		$retobj = array();
		
		$DBObj  = $GLOBALS['DBObj'];
		
		$query = "SELECT * FROM region_resources LEFT JOIN resources USING (resource_id) WHERE (region_id = $id)";
		$datatable = $DBObj->query($query);
		
		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			$resource = new RegionResource();
			$resource->region_resource_id = $storeitem["region_resource_id"];
			$resource->region_id = $storeitem["region_id"];
			$resource->resource_id = $storeitem["resource_id"];
			
			$resource->producerlimit = $storeitem["producer_limit"];
			$resource->producercount = $storeitem["producer_count"];
			$resource->producercost = $storeitem["producer_cost"];
			
			$resource->resourcecapacity = $storeitem["resource_capacity"];
			$resource->resourceextracted = $storeitem["resource_extracted"];
			$resource->productionrate = $storeitem["production_rate"];			
			
			$retobj[$storeitem["resource_id"]] = $resource;
		}

		return $retobj;
	}
		
	function loadRegion($id,$loadresources=false)
	{ 
		$DBObj  = $GLOBALS['DBObj'];
	
		$query = "SELECT region_coords_id,regions.region_id,name,population,coords FROM regions LEFT JOIN region_coords USING (region_id) WHERE (regions.region_id = $id)";
		$datatable = $DBObj->query($query);
	
		$retobj = new Region();
		$retobj->id = -1;	
	
		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			if ($retobj->id == -1)
			{
				$retobj->id = $storeitem["region_id"];
				$retobj->name = $storeitem["name"];
				$retobj->population = $storeitem["population"];
			}
				
			$retobj->pushCoords(explode(",",$storeitem["coords"]));
			array_push($retobj->coordsIDArray,$storeitem["region_coords_id"]);
			$i++;
		}	

		if ($retobj->id == -1)
			return null;
			
		if ($loadresources)
			$retobj->resources = $this->getResourceArray($id);
			
		return $retobj;		
	}
}

return 1;
?>