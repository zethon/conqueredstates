<?php 
//-----------------------------------------------------------------------------
// $RCSfile: users.php,v $ $Revision: 1.9 $ $Author: addy $ 
// $Date: 2006/06/30 21:16:31 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./includes/init.php');

class User
{
	var $userid = 0;
	var $username = "";
	var $password = "";
	var $email = "";
	var $countryid = "";
	var $lastlogin;
	
	function setProp($PropName, $PropValue) 
	{
		$this->$PropName = $PropValue;
	}	
	
	function UpdateLogin()
	{
		$DBObj  = $GLOBALS['DBObj'];
		$DBObj->query("UPDATE users SET lastlogin='".(date("Y-m-d H:i:s"))."' WHERE (userid = '$this->userid')");
	}		
}

class UserWriter
{
	function addUser($user)
	{
		$DBObj = $GLOBALS['DBObj'];					
			
		$query =  "INSERT INTO users (username,password,country_id) ";
		$query .= "VALUES ('".$user->username."','".$user->password."','".$user->countryid."')";
		$DBObj->query($query);
	
		return $DBObj->insert_id();
	}
}


class UserLoader
{
	function loadLobbyUser()
	{
		
	}
	
	function loadCookieUser()
	{
		$DBObj  = $GLOBALS['DBObj'];
		$login = strtolower(SafeQueryData($_COOKIE[$GLOBALS['gCookieLogin']]));		
		$password = SafeQueryData($_COOKIE[$GLOBALS['gCookiePassword']]);		

		if (strlen($login) > 0 && strlen($password) > 0)
		{
			$DBObj->query("UPDATE users SET lastlogin='".(date("Y-m-d H:i:s"))."' WHERE (username = '$login') AND (password = '$password')");		
				
			if ($DBObj->affected_rows() == 1)
				return $this->loadUser($login);
		}		
		
		return null;
	}
	
	
	function loadUser($username)
	{
		$DBObj = $GLOBALS['DBObj'];			
		
		$query = "SELECT DISTINCT * FROM users WHERE (username = '$username')";
		$data = $DBObj->query_first($query);

		if (strlen($data["userid"]) <= 0)
			return null;
						
		$retobj = new User();
		$retobj->userid = $data["userid"];
		$retobj->username = $data["username"];
		$retobj->password = $data["password"];
		$retobj->email = $data["email"];
		$retobj->countryid = $data["country_id"];
		$retobj->lastlogin = $data["lastlogin"];
	
		return $retobj;		
	}
}

return 1;
?>