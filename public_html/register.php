<?php
//-----------------------------------------------------------------------------
// $Workfile: register.php $ $Revision: 1.5 $ $Author: addy $ 
// $Date: 2006/06/02 21:33:02 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./global.php');

// save the user info
if ($_REQUEST['action'] == "doregister")
{
	$errorstr = "";
	$expiretime = time()+10;
	
	$newusername = strtolower(SafeQueryData($_REQUEST['username']));
	$pw1 = SafeQueryData($_REQUEST['pw1']);
	$pw2 = SafeQueryData($_REQUEST['pw2']);
	$email = SafeQueryData($_REQUEST['email']);
	$regpass = SafeQueryData($_REQUEST['regpass']);
	
	if (strtolower($regpass) != 'amirite!')
		$errorstr .= "H";
		
	if (strlen($newusername) < 3)
		$errorstr .= "A";
		
	if (strlen($pw1) < 3)
		$errorstr .= "B";
	
	if (strlen($pw2) < 3)
		$errorstr .= "C";
	else if ($pw1 != $pw2)
		$errorstr .= "D";
	
	if (strlen($email) < 3 || !eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$",$email))
		$errorstr .= "E";
				
	setcookie("temp_username",$newusername,$expiretime);				
	setcookie("temp_pw1",$pw1,$expiretime);
	setcookie("temp_pw2",$pw2,$expiretime);
	setcookie("temp_email",$email,$expiretime);
	
	if (strlen($errorstr) > 0)
	{
		setcookie("reg_errorstr",$errorstr,$expiretime); 
		header("Location: index.php?action=register");
		exit;
	}
	else
	{
		if (!@mysql_connect($dbhost,$dbuser,$dbpw))
		{
			$errorstr ="1"; // fatal! Unable to connect to db server
			setcookie("reg_errorstr",$errorstr,$expiretime); 			
			header("Location: index.php?action=register");
			exit;			
		}
		
		if (!@mysql_select_db($dbname))
		{
			//or die("(updateUser.php() Unable to select database [$dbname])");	
			$errorstr ="2"; // fatal! Unable to select db
			setcookie("reg_errorstr",$errorstr,$expiretime); 
			header("Location: index.php?action=register");
			exit;
		}
		 
		$result = mysql_query("SELECT * FROM users WHERE (username = '".$newusername."')");
		$num = mysql_numrows($result);	
		
		if ($num > 0)
			$errorstr .= "F";
	
		$result = mysql_query("SELECT * FROM users WHERE (email = '".$email."')");
		$num = mysql_numrows($result);	
		
		if ($num > 0)
			$errorstr .= "G";
	
		if (strlen($errorstr) > 0)
		{
			setcookie("reg_errorstr",$errorstr,$expiretime); 
			header("Location: index.php?action=register");
			mysql_close();
			exit;
		}		
		
		// no errors, create the user
		$query = "INSERT INTO users (username,email,password) VALUES ('$newusername','$email','".md5($pw1)."')";
		if (!mysql_query($query))
		{
			$errorstr = "3"; // fatal! insert user query failed!
			setcookie("reg_errorstr",$errorstr,$expiretime); 
			header("Location: index.php?action=register");
			mysql_close();
			exit;
		}
		
		// log the user in
		setcookie("e2k69_username",$newusername,time() + 3200,'/',$gURLDomain);
		setcookie("e2k69_id",md5($pw1),time() + 3200,'/',$gURLDomain);
		
		// redirect to the login
		header("Location: index.php?action=welcome");
		mysql_close();
		exit;
	}
?>

<?	
}
else // default - print the register form
{
	echo "<!-- errorstr [".$_COOKIE['reg_errorstr']."] -->\n";
	$errorstr = $_COOKIE['reg_errorstr'];
	
	// turn the string into a hash for easire acess
	for($i=0;$i<strlen($errorstr);$i++)
	{
		$errorHash{$errorstr{$i}} = true;
	} 
?>

<form name=mainform action=register.php method=post>
<input type=hidden name=action value=doregister>
<table width=100%>
<?
	if ($errorHash{'1'})
		echo "<tr><td colspan=2 align=center><font color=red>Database connection failure. :(</font></td></tr>\n";
	else if ($errorHash{'2'})
		echo "<tr><td colspan=2 align=center><font color=red>Database could not be opened. :(</font></td></tr>\n";
	else if ($errorHash{'2'})
		echo "<tr><td colspan=2 align=center><font color=red>Could not create user, please notify admin. :(</font></td></tr>\n";

	else if ($errorHash{'F'})
		echo "<tr><td colspan=2 align=center><font color=red>Username already in use</font></td></tr>\n";
	else if ($errorHash{'G'})
		echo "<tr><td colspan=2 align=center><font color=red>Email already in use</font></td></tr>\n";	
		
?>
<tr><td width=25%>Username:</td><td><input type=text name=username value='<? echo $_COOKIE["temp_username"]; ?>'></td></tr>
<?
	if ($errorHash{'A'})
		echo "<tr><td colspan=2><font color=red>Username too short</font></td></tr>\n";
?>

<tr><td width=25%>Password:</td><td><input type=password name=pw1 value='<? echo $_COOKIE["temp_pw1"]; ?>'></td></tr>
<?
	if ($errorHash{'B'})
		echo "<tr><td colspan=2><font color=red>password too short</font></td></tr>\n";
?>

<tr><td width=25%>Confirm Password:</td><td><input type=password name=pw2 value='<? echo $_COOKIE["temp_pw2"]; ?>'></td></tr>
<?
	if ($errorHash{'C'})
		echo "<tr><td colspan=2><font color=red>password confirmation too short</font></td></tr>\n";
	else if ($errorHash{'D'})
		echo "<tr><td colspan=2><font color=red>passwords do not match</font></td></tr>\n";
?>
<tr><td width=25%>Email:</td><td><input type=text name=email value='<? echo $_COOKIE["temp_email"]; ?>'></td></tr>
<?
	if ($errorHash{'E'})
		echo "<tr><td colspan=2><font color=red>invalid email!</font></td></tr>\n";
?>

<tr><td width=25%>Registration Password:</td><td><input type=password name=regpass></td></tr>
<?
	if ($errorHash{'H'})
	{
		echo "<tr><td colspan=2><font color=red>Invalid Registration Password</font></td></tr>\n";
		echo "<tr><td colspan=2>ConqueredStates is still under development and closed to public registration</td></tr>\n";
	}
?>

<tr><td colspan=2><input type=submit value=Register></td></tr>
</table>
</form>
<?	
}
?>