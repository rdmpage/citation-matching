<?php

// Identifier to CSL-JSON

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/wikidata.php');


$doc = null;
$doc = http_post_endpoint(["IDENTIFIER"]);


$doc->status = 404;

$namespace = '';

if (preg_match('/^Q\d+/', $doc->IDENTIFIER))
{
	$namespace = 'wikidata';
}

if (preg_match('/^10\.\d+/', $doc->IDENTIFIER))
{
	$namespace = 'doi';
}

switch ($namespace)
{
	case 'doi':
		$json = get('https://doi.org/' . $doc->IDENTIFIER, 'application/vnd.citationstyles.csl+json');
		$doc = json_decode($json);
		break;	
		
	case 'wikidata':
		$doc = wikidata_to_csl($doc->IDENTIFIER);
		break;		

	default:
		break;
}
	
if (isset($doc->title))
{	
	$doc->status = 200;
}

send_doc($doc);	

?>
