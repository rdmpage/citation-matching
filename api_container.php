<?php

// Get external ids for a container

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/db.php');

$doc = http_get_endpoint(["q"]);

// create a doc object
if (!isset($doc->{'container-title'}))
{
	$doc->{'container-title'} = $doc->q;
	unset($doc->q);
}
$doc->status = 404;

// can we get ISSN?
$issn = issn_from_title($doc->{'container-title'});

if (count($issn) > 0)
{
	$doc->status = 200;
	$doc->ISSN = $issn;
}

// BHL title via ISSN?	
if (isset($doc->ISSN))
{
	$title_id = get_bhl_title_from_issn($doc->ISSN[0]);
	if (count($title_id) > 0)
	{
		$doc->BHLTITLEID = $title_id;
		$doc->status = 200;
	}				
}

// If all else fails try title-based lookup
if (isset($doc->{'container-title'}) && !isset($doc->BHLTITLEID))
{
	$titles = get_bhl_title_from_text($doc->{'container-title'});
	if (count($titles) > 0)
	{
		$doc->BHLTITLEID = $titles;
		$doc->status = 200;
	}
}


send_doc($doc);	

?>
