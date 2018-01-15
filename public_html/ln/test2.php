<?
// gets the folder name of the current script

//function this_folder_name($path){
   //if (!$path){$path=$_SERVER['PHP_SELF'];}
   $current_directory = dirname($_SERVER['PHP_SELF']);
   $current_directory = str_replace('\\','/',$current_directory);
   $current_directory = explode('/',$current_directory);
   $current_directory = end($current_directory);
   
   print ("<h2>[".(dirname($_SERVER['PHP_SELF']))."]</h2>");
   print ("<h2>[".$_SERVER['PHP_SELF']."]</h2>");
	print ("<h1>[$current_directory]</h1>");
  // return $current_directory;
//}
//print this_folder_name();
?>
