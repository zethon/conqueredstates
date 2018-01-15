<?
//-----------------------------------------------------------------------------
// $Workfile: sidemenu.inc $ $Revision: 1.7 $ $Author: addy $ 
// $Date: 2006/07/14 00:46:38 $
//-----------------------------------------------------------------------------
?>
<table width=100% cellpadding=1 cellspacing=1 class="border_table" style="height: 100%">
<tr valign=top><td>

<table width=100%>
<?
	if ($gUser == null)
	{
		print ("<tr valign=top><td><a href='$gLobbyListGames' class=menuitem>Join Game</a></td></tr>\n");
		print ("<tr valign=top><td><hr></td></tr>\n");
	}
	else if ($gUser != null)
	{
		print ("<tr valign=top><td><a href='$gMainURL' class=menuitem>Home</a></td></tr>\n");
		print ("<tr valign=top><td><a href='$gMainURL/viewsettings.php' class=menuitem>Settings</a></td></tr>\n");
		
		print ("<tr valign=top><td><hr></td></tr>\n");
		
		print ("<tr valign=top><td><a href='$gMainURL/viewissues.php' class=menuitem>Issues</a></td></tr>\n");
		print ("<tr valign=top><td><a href='$gMainURL/viewbudget.php' class=menuitem>Budget</a></td></tr>\n");
		print ("<tr valign=top><td><a href='$gMainURL/viewresources.php' class=menuitem>Resources</a></td></tr>\n");
		print ("<tr valign=top><td><hr></td></tr>\n");
		print ("<tr valign=top><td><a href='$gMainURL/marketplace.php' class=menuitem>Marketplace</a></td></tr>\n");
		print ("<tr valign=top><td><hr></td></tr>\n");
	}
?>
<tr valign=top><td><a href="<? echo $gMainURL; ?>/viewnation.php" class=menuitem>View&nbsp;Nations</a></td></tr>
<tr valign=top><td><a href="<? echo $gMainURL; ?>/viewregion.php" class=menuitem>View&nbsp;Regions</a></td></tr>

<? if ($gUser != null) { ?>
	<tr valign=top><td><hr></td></tr>
	<tr valign=top><td><a href="<? echo "$gLobbyLogout"; ?>" class=menuitem>Logout</a></td></tr>
<? } ?>
<tr><td>&nbsp;</td></tr>
<tr><td align=center><a href="<? echo $gLobbyURL; ?>" target="_blank"><img border=0 src="<? echo "$gLobbyURL/images/game/linkimage.gif"; ?>" alt="Nation States"></a></td></tr>
</table>

</td></tr>
</table>
