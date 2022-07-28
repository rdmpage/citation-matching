<?php

// Container info


require_once(dirname(__FILE__) . '/database/sqlite.php');

//----------------------------------------------------------------------------------------
// ISSNs from title
function issn_from_title ($title)
{
	$issns = array();
	
	$sql = 'SELECT DISTINCT issn FROM issn WHERE title="' . addcslashes($title, '"') . '" COLLATE NOCASE;';

	//echo $sql;
	
	$result = do_query($sql);

	//print_r($result);

	foreach ($result as $row)
	{
		$issns[] = $row->issn;
	}
	
	return $issns;
}

//----------------------------------------------------------------------------------------


?>
