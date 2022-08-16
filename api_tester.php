<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/collation_parser.php');

$doc = http_get_endpoint(["q"]);

$doc->result = collation_parser($doc->q);

if (1)
{
	$doc->status = 200;
}
else
{
	$doc->status = 404;
}

send_doc($doc);	

?>
