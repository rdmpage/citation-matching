<?php

// BHL lookup using local BHL database

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/db.php');

//----------------------------------------------------------------------------------------
function find_bhl_page_local($doc)
{
	$sql = 'SELECT bhl_tuple.PageID, text 
			FROM bhl_tuple 
			INNER JOIN bhl_page USING(PageID)
			WHERE '
		. ' TitleID=' . $doc->BHLTITLEID[0]
		. ' AND sequence_label="' . str_replace('"', '""', $doc->volume) . '"'
		. ' AND page_label="' . str_replace('"', '""', $doc->page)  . '"';
		
	// echo $sql . "\n";

	$results = do_query($sql);
	
	//print_r($result);
	
	foreach ($results as $result)
	{
		$doc->BHLPAGEID[] = $result->PageID;
		$doc->text[$result->PageID] = $result->text;	
	}
	
	return $doc;
}

//----------------------------------------------------------------------------------------

$required = array('container-title', 'volume', 'page');
$doc = http_post_endpoint($required);

// test
if (0)
{
	$doc = new stdclass;
	$doc->status = 200;
	$doc->{'container-title'} = 'Nota lepid.';
	$doc->volume = '25';
	$doc->page = '130';
}

// BHL title	
if (isset($doc->ISSN))
{
	$title_id = get_bhl_title_from_issn($doc->ISSN[0]);
	if (count($title_id) > 0)
	{
		$doc->BHLTITLEID = $title_id;
	}				
}

// hack
if (isset($doc->{'container-title'}) && !isset($doc->BHLTITLEID))
{
	$titles = get_bhl_title_from_text($doc->{'container-title'});
	if (count($titles) > 0)
	{
		$doc->BHLTITLEID = $titles;
	}
}


if (isset($doc->BHLTITLEID))
{
	// BHL page
	$doc = find_bhl_page_local($doc);
}


send_doc($doc);	

?>
