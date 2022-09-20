<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/db.php');
require_once (dirname(dirname(__FILE__)) . '/microparser.php');

$doc = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	$doc = http_get_endpoint(["q"]);
}
else
{
	$doc = http_post_endpoint(["q"]);
}

$parse_result = parse($doc->q);

// we want to add results to existing doc
foreach ($parse_result  as $k => $v)
{
	$doc->{$k} = $v;
}

if (0)
{
	// do we want to do this here, or keep this as a pure parser?
	// embellish with ids if we can 
	if (isset($doc->{'container-title'}))
	{
		$issn = issn_from_title($doc->{'container-title'});
	
		if (count($issn) > 0)
		{
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
	
	}
}

send_doc($doc);	

?>
