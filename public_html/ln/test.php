<?	

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


$number = 9876543;

$p = GetNumberPhrase($number);

print ("<h1>[$p]</h1>\n");


?>