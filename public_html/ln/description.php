<?
//-----------------------------------------------------------------------------
// $RCSfile: description.php,v $ $Revision: 1.5 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./nations.php');
require_once('./issues.php');

define('NUMBER_OPTION_PHRASES',3);

class Blurb
{
	var $blurb_id = 0;
	var $longtext;
	
	function GetNumberPhrase($int)
	{
		$retval = "";
	
		if ($int < 1000) // 847
			$retval = $int;
		else if ($int < 1000000)
			$retval = substr_replace($int,'',strlen($int)-3,3)." thousand";
		else if ($int < 1000000000)
			$retval = substr_replace($int,'',strlen($int)-6,6)." million";
		else if ($int < 1000000000000)
			$retval = substr_replace($int,'',strlen($int)-9,9)." billion";
		else if ($int < 1000000000000000)
			$retval = substr_replace($int,'',strlen($int)-12,12)." trillion";
		else 
			$retval = $int;
	
		return $retval;
	}	
	
	function Blurb($data = null)
	{
		if ($data == null)
			return;
			
		if (is_int($data))
			$this->Load($data);
		else
			$this->longtext = $data;
	}
	
	function setProp($PropName, $PropValue) 
	{
	   $this->$PropName = $PropValue;
	}		
	
	function Load($id)
	{
		$DBObj  = $GLOBALS['DBObj'];
		$data =  $DBObj->query_first("SELECT * FROM blurbs WHERE (blurb_id = '$id')");
		
		$retval = false;
		if ($data['blurb_id'] > 0)
		{
			$retval = true;
			
			$vars = get_object_vars($this);
			foreach ($vars as $name=>$val) 
			{
				$this->setProp($name,$data[$name]);
			}
		}
		
		return $retval;
	}
	
	function ExpandNationVariables($nation)
	{
		$text = $this->longtext;
		
		$vars = get_object_vars($nation);
		foreach ($vars as $name=>$val) 
		{
			if (!is_object($nation->$name) && !is_array($nation->$name))
			{
				$varname = strtoupper("%NATION:$name%");
				
				$newval = $nation->$name;
				if (is_integer($newval))
					$newval = number_format($newval);
				
				if (strtolower($name) == 'name')	
					$text = str_replace($varname,ucfirst($newval),$text);
				else
					$text = str_replace($varname,$newval,$text);
			}
		}		
		
		// replace the non variable types
		$text = str_replace("%NATION:POPULATION%",$this->GetNumberPhrase($nation->GetPopulation()),$text);
	
		// set the object property
		$this->setProp('longtext',$text);
		return $this->longtext;
	}
}

class NationDesc
{
	
	
	function GetEmptyDescription($nation)
	{
		$retstr = "";
		$blurb = new Blurb();
		
		if ($blurb->Load(1))
		{
			$blurb->ExpandNationVariables($nation);
			return $blurb->longtext;
		}
		
		$retstr  = "The $nation->title of $nation->name is not very well known. ";
		$retstr .= "This nation of ".(number_format($nation->GetPopulation()))." people uses the $nation->currency as its national currency. There have been reports of large ";
		$retstr .= "demonstrations where the citizens wave the national flag and chant \"$nation->motto\".";
		return $retstr;		
	}
	
	function GetOptionPhrasesSentence($nation)
	{
		$DBObj  = $GLOBALS['DBObj'];
		$datatable = $DBObj->query("SELECT * FROM nation_issues LEFT JOIN issue_options USING (issue_option_id) WHERE (option_phrase IS NOT NULL) AND (nation_id = '$nation->id') AND (nation_issues.status = '2')"); //TODO: rework that 2 to be the ISSUE_STATUS_SET define
		$sentence = array();

		// create array of phrases to chose from
		$phrases = array();
		while ($storeitem = $DBObj->fetch_array($datatable))
			array_push($phrases,new IssueOption($storeitem));
		
		print ("\n<!-- DEBUG INFO\n");
		$selected = array();		
		$loops = min(count($phrases),NUMBER_OPTION_PHRASES);
		for($i = 0; $i < $loops; $i++)
		{
			$rowcount = count($phrases);
			if ($rowcount == 0)
				continue;
				
			$done = false; 
			while (!$done)
			{
				$idx = mt_rand(0,$rowcount-1);
				$option = $phrases[$idx];

				if (!isset($selected[$option->issue_option_id]))
				{
					print ("ISSUE_OPTION_ID: ($option->issue_option_id) | ISSUE ID: ($option->issue_id)\n");					
					array_push($sentence,$option->option_phrase);
					$selected[$option->issue_option_id] = 1;
					$done = true;
					array_splice($phrases,$idx,1);
				}
			}			
		}
		print (" /DEBUG INFO -->\n");
		
		// build the actual sentence text
		$sentencestr = "";
		for ($i = 0; $i < count($sentence); $i++)
		{
			if ($i == 0)
				$sentencestr .= ucfirst($sentence[$i]);
			else if ($i+1 == count($sentence))
				$sentencestr .= ", and ".$sentence[$i].".";
			else 
				$sentencestr .= ", ".$sentence[$i];
		}
		
		if ($count == 1)
			$sentencestr .= ".";
		
		return $sentencestr;		 
	}
	
	function GetGenericDescription($nation)
	{
		//var $popadjs = array
		if ($nation == null)
			return null;
		else if ($nation->stats == null)
			return $this->GetEmptyDescription($nation);

		if ($nation->ecstats == null)
			$nation->CalcEconomicStats();		
			
		// nation size description based on regions
		if (count($nation->regions) < 3)
			$nationsize = "tiny";
		else if (count($nation->regions) < 5)
			$nationsize = "small";
		else if (count($nation->regions) < 7)
			$nationsize = "moderatly sized";
		else if (count($nation->regions) < 9)
			$nationsize = "large";
		else
			$nationsize = "very large";		
			
		if ($nation->stats->civil_liberties < 2)
			$cl = "are routinely rounded up and imprisoned for looking at their leader the wrong way";
		else if ($nation->stats->civil_liberties < 4)
			$cl = "are controlled by a ruthless leader";
		else if ($nation->stats->civil_liberties < 6)
			$cl = "enjoy some freedoms and are regulated by strict laws with harsh punishments";
		else if ($nation->stats->civil_liberties < 8)
			$cl = "live in a free society protected by a strong legal system";
		else if ($nation->stats->civil_liberties <= 10)
			$cl = "are reckless and free to do what they want with little regard for others";
		
		if ($nation->stats->political_freedom < 2)
			$pf = "most citizens are not allowed to vote";
		else if ($nation->stats->political_freedom < 4)
			$pf = "vote in mock elections where there is often only one name on the ballot";
		else if ($nation->stats->political_freedom < 6)
			$pf = "elect officials in a puppet government that has little control over the nation";
		else if ($nation->stats->political_freedom < 8)
			$pf = "often elect corrupt and power hungry officials";
		else if ($nation->stats->political_freedom <= 10)
			$pf = "are very active in the governing of their nation";			
		
		$retstr	.= "The %NATION:TITLE% of %NATION:NAME% is a $nationsize nation with %NATION:POPULATION% people. Citizens of %NATION:NAME% $cl, and $pf.";
			
		$temp = $this->GetOptionPhrasesSentence($nation);
		if (strlen($temp) > $temp)	
			$retstr .= "<p>$temp</p>";
			
		if ($nation->taxrate > 0)
			$taxstr = "The average income tax rate is ".number_format($nation->taxrate * 100)."%";
		else
			$taxstr = "Income tax is unheard of";
		
		if ($nation->stats->economic_strength < 1)
			$eh = "is on the verge of collapse";
		else if ($nation->stats->economic_strength < 3)
			$eh = "is heavily dependent on subsistence living";
		else if ($nation->stats->economic_strength < 5)
			$eh = "is highly sensitive to fluctuations in international prices";
		else if ($nation->stats->economic_strength < 7)
			$eh = "depends primarily on a well-developed services sector";
		else if ($nation->stats->economic_strength < 9)
			$eh = "is expanding its presence in world markets";
		else if ($nation->stats->economic_strength < 10)
			$eh = "dominates the international region";
		
		if ($nation->stats->economic_health < 1)	
			$es = "live in squalor in tent cities";
		else if ($nation->stats->economic_health < 3)
			$es = "mostly scavenge for goods and beg for money";
		else if ($nation->stats->economic_health < 5)
			$es = "are usually either very poor or very wealthy";
		else if ($nation->stats->economic_health < 8)
			$es = "are often rewarded finacially for hard work";
		else if ($nation->stats->economic_health < 10)
			$es = "enjoy life in a rich prosperous environment";
			
		if ($nation->ecstats->consconf < .1)
			$cc = "consumers horde goods and resources in preparation for finacial ruin";
		else if ($nation->ecstats->consconf < .3)
			$cc = "consumer confidence is at an all time low";
		else if ($nation->ecstats->consconf < .5)
			$cc = "consumers remain skeptical and  spend their income wisely";
		else if ($nation->ecstats->consconf < .7)
			$cc = "consumers spend and save their money freely";
		else if ($nation->ecstats->consconf < 1)
			$cc = "consumer confidence is high as consumers spend their money whimsically";
			
		if ($nation->ecstats->govteff < .8)
			$ge = "is highly corrupt and unresposive to the needs of it's citizens";
		else if ($nation->ecstats->govteff < .85)	
			$ge = "is easily bribed and highly influential for the right price";
		else if ($nation->ecstats->govteff < .90)	
			$ge = "sometimes meets the needs of its citizenry and often ignores them";
		else if ($nation->ecstats->govteff < .95)	
			$ge = "treats is citizens well and fair";
		else if ($nation->ecstats->govteff < 1)	
			$ge = "is a model for nations all everywhere";
			


		$retstr .= "<p>In %NATION:NAME% $cc, and the government $ge. This $nationsize economy $eh, and citizens $es. $taxstr, unemployment is at ".(number_format($nation->ecstats->unemployment*100))."%, and the national currency is the $nation->currency.   </p>";
			
	
		
		$blurb = new Blurb($retstr);
		return $blurb->ExpandNationVariables($nation);		 
	}
}
?>