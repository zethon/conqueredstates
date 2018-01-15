<?php 
//-----------------------------------------------------------------------------
// $RCSfile: issues.php,v $ $Revision: 1.8 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
require_once('./includes/global.php'); 
require_once('./includes/init.php'); 
require_once('./description.php');

define(ISSUE_STATUS_PENDING,0);
define(ISSUE_STATUS_MODIFIED,1);
define(ISSUE_STATUS_SET,2);

class NationIssueChoice
{
	var $nation_issue_id;
	var $issue_id;
	var $issue_option_id;
	var $old_issue_option_id;
	var $lastmodified;
	var $nation_id;
	var $status;
	
	function NationIssueChoice($data = null)
	{
		if ($data == null)
			return;
			
		$vars = get_object_vars($this);
		foreach ($vars as $name=>$val) 
		{
			if (isset($data[$name]))
				$this->$name = $data[$name];
		}
	}

	function setProp($PropName, $PropValue) 
	{
		$this->$PropName = $PropValue;
	}		
	
	function Create($nationid=null,$issueid=null)
	{
		if ($nationid != null)
			$this->nation_id = $nationid;

		if ($issueid != null)
			$this->issue_id = $issueid;
			
		$DBObj = $GLOBALS['DBObj'];
		$DBObj->query("INSERT INTO nation_issues (nation_id,issue_id) VALUE ('$this->nation_id','$this->issue_id')");
		
		return $this->nation_issue_id = $DBObj->insert_id();				
	}
	
	function Save($upmod = true)
	{
		$DBObj = $GLOBALS['DBObj'];
		
		if ($upmod == true)
			$lm = ",lastmodified='".(date("Y-m-d H:i:s"))."'";
		
		$DBObj->query("UPDATE nation_issues SET old_issue_option_id='$this->old_issue_option_id',issue_option_id='$this->issue_option_id',status='$this->status'$lm WHERE (nation_issue_id = '$this->nation_issue_id')");
		
		return ($DBObj->affected_rows() > 0);
	}
	
	function CanMakeNewDecision()
	{
		if ($this->status == ISSUE_STATUS_PENDING)
			return true;
			 			
		$modtime = strtotime($this->lastmodified);
		
		if ((time() - $modtime) > (60*60*24)) // can only edit every 12 hours
			return true;
		
		return false;
	}		
}


class IssueOption
{
	var $issue_option_id;
	var $option_text;
	var $option_phrase;
	var $mods;
	var $issue_id;

	function IssueOption($data = null)
	{
		if ($data == null)
			return;
			
		$vars = get_object_vars($this);
		foreach ($vars as $name=>$val) 
		{
			if (is_object($this->$name) || is_array($this->$name))
				continue;
				
			if (isset($data[$name]))
				$this->$name = $data[$name];
		}
	}		
	
	
	function setProp($PropName, $PropValue) 
	{
		$this->$PropName = $PropValue;
	}		
	
	function GetOptionText($nation = null)
	{
		if ($nation == null)
			return $this->option_text;
			
		$blurb = new Blurb($this->option_text);
		return $blurb->ExpandNationVariables($nation);			
	}
	
	function Save()
	{
		$DBObj = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE issue_options SET option_phrase='".(@SafeQueryData($this->option_phrase))."',option_text='$this->option_text',mods='$this->mods' WHERE (issue_option_id = '$this->issue_option_id')");
		//print("UPDATE issue_options SET option_phrase='".(@SafeQueryData($this->option_phrase))."',option_text=\"".(($this->option_text))."\",mods='$this->mods' WHERE (issue_option_id = '$this->issue_option_id')");
		//print ("<hr>");
		
		return ($DBObj->affected_rows() > 0);				
	}
	
	function Create($issue_id)
	{
		$DBObj = $GLOBALS['DBObj'];
		$DBObj->query("INSERT INTO issue_options (issue_id) VALUE ('$issue_id')");
	}
}

class Issue
{
	var $issue_id;
	var $text;
	var $title;
	
	var $options = array();
	var $nchoice = null;
	var $nation = null;
	
	function Issue($data = null)
	{
		if ($data == null)
			return;
			
		$vars = get_object_vars($this);
		foreach ($vars as $name=>$val) 
		{
			if (is_object($this->$name) || is_array($this->$name))
				continue;
				
			if (isset($data[$name]))
				$this->$name = $data[$name];
		}
	}	
	
	function LoadNationChoice($nat = null)
	{
		if ($nat == null)
			$nation = $this->nation;
			
		if ($nat != null)
			$nation = $nat;
			
		if ($nation == null)
			return false;
		
		$DBObj = $GLOBALS['DBObj'];
		
		$data = $DBObj->query_first("SELECT * FROM nation_issues WHERE (nation_id = '$nation->id') AND (issue_id = '$this->issue_id')");

		if ($data != null)
		{
			$nchoice = new NationIssueChoice();
			$nchoice->nation_issue_id = $data['nation_issue_id'];	
			$nchoice->issue_option_id = $data['issue_option_id'];
			$nchoice->old_issue_option_id = $data['old_issue_option_id'];
			$nchoice->lastmodified = $data['lastmodified'];
			$nchoice->nation_id = $data['nation_id'];
			$nchoice->issue_id = $data['issue_id'];
			$nchoice->status = $data['status'];
			$this->nchoice = $nchoice;
		}
		
		return !($nchoice == null);
	}
	
	function GetVariableText($var = null, $nat = null)
	{
		if ($nat == null)
			$nation = $this->nation;
			
		if ($nat != null)
			$nation = $nat;
					
		if ($nation == null && $var == null)
			return null;
		
		if ($this->$var == null)
			return null;
			
		$blurb = new Blurb($this->$var);
		return $blurb->ExpandNationVariables($nation);		
	}
		
	function setProp($PropName, $PropValue) 
	{
		$this->$PropName = $PropValue;
	}			
	
	function Save()
	{
		$DBObj = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE issues SET text='$this->text',title='$this->title' WHERE (issue_id = '$this->issue_id')");
		
		return ($DBObj->affected_rows() > 0);		
	}
	
	function Create()
	{
		$DBObj = $GLOBALS['DBObj'];
		$DBObj->query("INSERT INTO issues (text,title) VALUES ('New Issue','New Title')");
		return $DBObj->insert_id();
	}
}

class IssueLoader
{
	function LoadIssue($id,$nation = null)
	{
		$retval = null;
		$DBObj = $GLOBALS['DBObj'];
		
		$datatable = $DBObj->query("SELECT * FROM issues LEFT JOIN issue_options USING (issue_id) WHERE (issues.issue_id = '$id') ORDER BY issue_option_id");
		while ($storeitem = $DBObj->fetch_array($datatable))
		{
			if ($storeitem['issue_id'] > 0 && $retval == null)
			{
				$retval = new Issue();
				$retval->setProp('issue_id',$storeitem['issue_id']);
				$retval->setProp('text',$storeitem['text']);
				$retval->setProp('title',$storeitem['title']);				
			}
		
			if ($storeitem['issue_option_id'] > 0 && $retval != null)
			{
				$option = new IssueOption();
				$option->setProp('issue_option_id',$storeitem['issue_option_id']);
				$option->setProp('option_text',$storeitem['option_text']);
				$option->setProp('mods',$storeitem['mods']);
				$option->setProp('option_phrase',$storeitem['option_phrase']);
				//array_push($retval->options,$option);				
				$retval->options[$storeitem['issue_option_id']] = $option;
			}					
		}
		
		if ($nation != null && $retval != null)
		{
			$retval->nation = $nation;
			$retval->LoadNationChoice();
		}
			
		return $retval;		
	}
}

class NationModifier
{
	var $varname;
	var	$limit1;
	var $limit2;
	
	function DoModify($nation)
	{
	}
	
	function UndoModify($nation)
	{
	}
		
	function GetRandomValue()
	{
	}		
		
	function SetBounds($l1,$l2)
	{
		$this->limit1 = $l1;
		$this->limit2 = $l2;
	}
}

class StatModifier extends NationModifier
{
	function GetRandomValue()
	{
		$min = $this->limit1;
		$max = $this->limit2;
		
		if ($min == $max)
			return $min;
			
		$tempa = $min * 1000;
		$tempb = $max * 1000;

		if ($tempa > $tempb)
			$value = mt_rand($tempb,$tempa);
		else
			$value = mt_rand($tempa,$tempb);
		
		return ($value * .001);	
	}	

	function DoModify($nation)
	{
		return $nation->stats->ModifyStat($this->varname,$this->GetRandomValue());
	}
	
	function UndoModify($nation)
	{
		$val = $this->GetRandomValue();
		
		if (substr($val,0,1) != '-')
			$val = '-'.$val;
		else
			$val = trim($val,'-');
			
		$nation->stats->ModifyStat($this->varname,$val);
	}
}

// TODO: add class NationModifier, to edit the balance, taxrate, etc..

class ExpenseModifier extends NationModifier
{
	function GetRandomValue()
	{
		$min = $this->limit1;
		$max = $this->limit2;
		
		if ($min == $max)
			return $min;
			
		$tempa = $min * 100;
		$tempb = $max * 100;

		if ($tempa > $tempb)
			$value = mt_rand($tempb,$tempa);
		else
			$value = mt_rand($tempa,$tempb);
		
		return ($value * .01);	
	}		
	
	function DoModify($nation)
	{
		$nation->expenses->ModifyExpense($this->varname,$this->GetRandomValue());
	}
	
	function UndoModify($nation)
	{
//		$val = $this->GetRandomValue();
//		
//		if (substr($val,0,1) != '-')
//			$val = '-'.$val;
//		else
//			$val = trim($val,'-');
//			
//		$nation->expenses->ModifyExpense($this->varname,$val);
	}
}

class TextModTokenizer
{
	function Tokenize($string)
	{
		$string = preg_replace('/\s/','',$string);
		$retval = array();

		foreach (split(";",$string) as $onemod)
		{
			list ($key,$varname,$intblob) = split(":",$onemod);
			
			if (!isset($key) || !isset($varname) || !isset($intblob))
				continue; // bad mod
				
			list ($val1,$val2) = split(",",$intblob);
			
			if (!isset($val1))
				continue; // bad mod
				
			if (!isset($val2))
				$val2 = $val1;
			
			switch ($key)
			{
				case 'stats':
					$newmod =& new StatModifier();
				break;
				
				case 'expenses':
					$newmod =& new ExpenseModifier();
				break;
				
				default:
					$newmod = null;
				break;
			}			
			
			if ($newmod != null)
			{
				$newmod->varname = $varname;
				$newmod->SetBounds($val1,$val2);
				array_push($retval,$newmod);
			}
		}
		
		return $retval;
	}	
}
?>