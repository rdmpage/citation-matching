<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/db.php');

$doc = http_get_endpoint(["q"]);

$issn = issn_from_title($doc->q);

if (count($issn) > 0)
{
	$doc->status = 200;
	$doc->ISSN = $issn;
}
else
{
	$doc->status = 404;
}

send_doc($doc);	

?>
