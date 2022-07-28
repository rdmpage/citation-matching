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
// BHL title lookup based on ISSN
function get_bhl_title_from_issn ($issn)
{
	$titles = array();
	
	$sql = 'SELECT * FROM titleidentifiertxt WHERE identifiername="ISSN" AND identifiervalue="' . $issn . '";';

	$result = do_query($sql);
	
	foreach ($result as $row)
	{
		$titles[] = $row->titleid;
	}
	
	// hack to add missing titles	
	if (count($titles) == 0)
	{
		switch ($issn)
		{
			case '0035-8894':
				$titles[] = 11516;
				break;
			
			default:
				break;
		}
	}		
	
	return $titles;
}

?>
