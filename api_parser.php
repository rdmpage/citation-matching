<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/db.php');
require_once (dirname(__FILE__) . '/microparser.php');

$doc = http_get_endpoint(["q"]);

$doc = parse($doc->q);

if (isset($doc->{'container-title'}))
{
	$issn = issn_from_title($doc->{'container-title'});
	
	if (count($issn) > 0)
	{
		$doc->ISSN = $issn;
	}	
	
}

send_doc($doc);	

?>
