<?
//-----------------------------------------------------------------------------
// $Workfile: listgames.php $ $Revision: 1.3 $ $Author: addy $ 
// $Date: 2006/05/23 21:36:46 $
//-----------------------------------------------------------------------------
error_reporting(E_ALL & ~E_NOTICE & ~ E_WARNING);
require_once('./global.php');

if (!$gLoggedIn)
	exit;


mysql_connect($dbhost,$dbuser,$dbpw);
@mysql_select_db($dbname) or die("(updateUser.php() Unable to select database [$dbname])");	
$result = mysql_query("SELECT * FROM games");
$num = mysql_numrows($result);

?>

<table border=0 cellspacing=0 cellpadding=1 width=100%><tr><td bgcolor=#FFFACD>   
<table width=100% bgcolor=000000 cellspacing=0 cellpadding=2>
<tr class=box_header><td align=cente>Status</td><td>Game URL</td><td align=center># of Nations</td><td align=center>Unclaimed Regions</td><td>&nbsp;</td></tr>

<?
$xml_parser = xml_parser_create();
xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);		

$i = 0;
while ($i < $num)
{
	$status = "<div class=error_text>Offline</div>";
	$nationcount = 0; $freeregions = 0;
	$joinlink = "";
	
	$gameurl = mysql_result($result,$i,"url");	
	$querykey = mysql_result($result,$i,"key");
	
	if (($i % 2) == 0)
		$boxclass = "box_content1";
	else
		$boxclass = "box_content2";

	$contents = implode ('', file("$gameurl/query.php?key=$querykey")); 
	
	if (strlen($contents) > 0)
	{
		$vals = array(); $tags = array();
		xml_parse_into_struct($xml_parser, $contents, $vals, $tags);

		if ($vals[2][tag] == "NATIONCOUNT")
			$nationcount = $vals[2][value];
		
		if ($vals[1][tag] == "FREEREGIONS")
			$freeregions = $vals[1][value];
	}
	
	if (strlen($contents) > 0)
	{
		if ($freeregions > 0)
			$status = "<div class=normal_text>Open</b></div>";
		else
			$status = "<div class=error_text>Closed</div>";
			
		$joinlink = "<a class=welcome href='$gameurl/join.php'>Join</a>";
		$gameurl = "<a href='$gameurl' class=welcome target=_new>$gameurl</a>";
	}
	else
	{
		$nationcount = "n/a";
		$freeregions = "n/a";
	}
		
	echo "<tr class=$boxclass><td>$status</td><td>$gameurl</td><td align=center>$nationcount</td><td align=center>$freeregions</td><td align=center>$joinlink</td></tr>\n";
	
	$i++;
}

xml_parser_free($xml_parser);		
mysql_close();
?>
</table>
</td></tr></table>