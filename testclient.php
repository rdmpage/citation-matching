<?php

// Client to test workflows 

require_once (dirname(__FILE__) . '/api_utilities.php');


$citations = array(
	'Paradoris anaphracta' => 'J. Bombay nat. Hist. Soc. 17: 740.', 
);

foreach ($citations as $name => $citation)
{
	$url = 'http://localhost/citation-matching/api_parser.php?';
	
	$parameters = array(
		'q' => $citation
	);
	
	$json = get($url . http_build_query($parameters));
	
	$obj = json_decode($json);
	
	print_r($obj);
}

?>
