<?php
//-----------------------------------------------------------------------------
// $Workfile: index.php $ $Revision: 1.4 $ $Author: addy $ 
// $Date: 2006/05/23 21:36:46 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE);
require_once('./global.php');

$gCookieUsername = strtolower(SafeQueryData($_COOKIE['e2k69_username']));

// is the user logged in?
$gLoggedIn = @isValidUser($gCookieUsername,SafeQueryData($_COOKIE['e2k69_id']));
$login_error = false;

if ($_REQUEST['action'] == "logout")
{
	setcookie ("e2k69_username",$_COOKIE['e2k69_username'], time()-3200,'/',$gURLDomain); 
	setcookie ("e2k69_id",$_COOKIE['e2k69_id'], time()-3200,'/',$gURLDomain); 	
	$gLoggedIn = false;
}
else
if ($gLoggedIn == false && $_REQUEST['action'] == "login")
{
	if (@isValidUser(strtolower($_REQUEST['username']),md5($_REQUEST['password'])))
	{
		if ($_POST['keep'] == 'on')
			$exTime = time() + 60*60*24*365;
		else
			$exTime = time() + 3200;
						
		// log the user in
		setcookie("e2k69_username",strtolower($_REQUEST['username']),$exTime,'/',$gURLDomain);
		setcookie("e2k69_id",md5($_REQUEST['password']),$exTime,'/',$gURLDomain);	
		$gCookieUsername = strtolower(SafeQueryData($_REQUEST['username']));
		$gLoggedIn = true;
	}
	else
	{
		$login_error = true;
	}
}
?>

<html>
<head>
<title>Conquered States</title>
</head>

<link rel="stylesheet" href="style.css" type="text/css">

<body bgcolor=black text=white>
<?
	TRACE("<!-- user[".$_COOKIE['e2k69_username']."] id[".$_COOKIE['e2k69_id']."]-->");
?>
<table>
<table border="0" width="800px" align="center" cellspacing="0" cellpadding="0">
<tr><td align=center><div class=title_text>Conquered States</div><img src="images/globe.jpg"></td></tr>
<tr><td>&nbsp;</td></tr>
<tr>
<td>


<table width=100% cellspacing="0" cellpadding="0">
<tr valign=top>
<td width=15%>
<!-- BEGIN LEFT SIDE MENU -->

<?
	if (!$gLoggedIn)
	{
?>
<!-- LOGIN FORM -->
<form method=post action=index.php name=mainform>
<input type=hidden name=action value=login>
<table border=0 cellspacing=0 cellpadding=1 width=100%><tr><td bgcolor=#FFFACD>   
<table width=100% bgcolor=000000 cellspacing=0 cellpadding=2>
<?
	 if ($login_error)
	 	echo "<tr><td colspan=2><font color=red>invalid login</font></td></tr>\n";
?>
<tr><td class=normal_text>Login</td><td><input type=text class=text_input name=username></td></tr>
<tr><td class=normal_text>Password</td><td><input type=password class=text_input name=password></td></tr>
<tr><td colspan=2>
<table cellpadding=0 cellspacing=0 width=100%>
<tr><td class=small_text>Remember me <input type=checkbox name=keep></td><td align=right><input type=submit class=submit_button value="Login"></td></tr>
</table>
</td></tr>
</table>
</td></tr></table>
</form>
<!-- /LOGIN FORM -->
<br>
<?
	}
?>

<table>
<?
	if ($gLoggedIn)
	{
		echo "<tr><td colspan=2 class=normal_text>Hello ".$gCookieUsername."!</td></tr>\n";
	}
?>
<tr><td class=left_menu_text><a class=welcome href="index.php">Home</a></td></tr>
<tr><td class=left_menu_text><a class=welcome href="index.php?action=faq">FAQ</a></td></tr>
<? 
	if (!$gLoggedIn)
	{
?>
<tr><td class=left_menu_text><a class=welcome href="index.php?action=register">Register</a></td></tr>
<?
	}
	else if ($gLoggedIn)
	{
?>
<tr><td class=left_menu_text><a class=welcome href="index.php?action=listgames">List All Games</a></td></tr>
<tr><td class=left_menu_text><a class=welcome href="index.php?action=logout">Logout</a></td></tr>
<?
	}
?>


</table>



<!-- END LEFT SIDE MENU -->
</td>

<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>
<td>&nbsp;</td>

<td>
<!-- BEGIN MAIN CONTENT TABLE -->

<?
	echo "<!-- action [".$_REQUEST['action']."];gLoggedIn [".$gLoggedIn."] -->";
	if (strtolower($_REQUEST['action']) == 'faq')
		include "faq.html";
	else if (strtolower($_REQUEST['action']) == 'listgames')
		include "listgames.php";		
	else if (strtolower($_REQUEST['action']) == 'register')
		include "register.php";	
	else
		include "home.html";
?>
<!-- END MAIN CONTENT TABLE -->
</td>
</tr>
</table>




</td>
</tr>
</table>

</body>
</html>