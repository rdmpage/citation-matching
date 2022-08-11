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

//----------------------------------------------------------------------------------------
// BHL title from string (hack if we don't have identifier mapping
// Return as array as we may have > 1 TitleIDs for the same title
function get_bhl_title_from_text($text)
{
	$titles = array();
	
	switch ($text)
	{
		case 'Ann. S. Afr. Mus.':		
			$titles = array(6928);
			break;
			
		case 'Deutsche entomologische Zeitschrift Iris':
		case 'Dt. ent. Z. Iris':
			$titles = array(12260);
			break;
	
		case 'Exot. Micr.':
		case 'Exotic Microlep.':
		case 'Exot. Microlepid.':
		case 'Exotic microlepidoptera':
			$titles = array(9241);
			break;			
	
		case 'Genera Insectorum':
			$titles = array(45481);
			break;
			
		case 'Isis von Oken':
		case 'Isis, Leipzig':
			$titles = array(13271);
			break;
			
		case 'List Specimens lepid. Insects Colln Br. Mus.':
			$titles = array(58221);
			break;		
			
		case 'J. Straits Asiat. Soc.':
			$titles = array(64180);
			break;
			
		case 'Telopea':
			$titles = array(157010);
			break;
		
		default:
			break;
	}	
	
	return $titles;
}



?>
