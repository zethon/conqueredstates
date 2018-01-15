<?
//-----------------------------------------------------------------------------
// $Workfile: banner.inc $ $Revision: 1.3 $ $Author: addy $ 
// $Date: 2006/06/12 21:10:15 $
//-----------------------------------------------------------------------------
?>
<img src="images/banner.gif" alt="Conquered States">
<table width=100% cellpadding=0 cellspacing=0>
<tr valign=top>
<? 
	if ($gUser)
	{
?>
<td align=left class=tiny_text>&nbsp;<b>User</b>: <i><? echo $gUser->username; ?></i></td>
<?
	}
?>
<td align=right class=tiny_text><b>Server</b>: <i><? echo $gGameServerName; ?></i>&nbsp;</td></tr>
</table>