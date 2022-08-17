<?php

// Generate abbreviations for journal names in ISSN database that aren't in that database

require_once ('../abbreviation.php');


$sql = 'SELECT * FROM issn WHERE NOT title LIKE "%.%"';

$data = do_query($sql);


foreach ($data as $obj)
{
	echo $obj->title;
	
	$abbreviated = abbreviated_title($obj->title );
	
	echo  " = $abbreviated";
	
	// exists
	$sql = 'SELECT * FROM issn WHERE title="' . str_replace('"', '""', $abbreviated) . '"';
	
	//echo "\n$sql\n";
	
	$result = do_query($sql);
	
	if (count($result) == 0)
	{
		echo " *";
	}
	echo "\n";
	
	

}


?>

