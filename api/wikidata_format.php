<?php

// Wikidata to CSL, all done here

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/wikidata.php');

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;


$doc = null;
$doc = http_post_endpoint(["WIKIDATA"]);

if (0)
{
	$doc = new stdclass;
	$doc->WIKIDATA = 'Q92901422';
	$doc->style = 'apa';
}

if (!isset($doc->style))
{
	$doc->style = "apa";
}

$doc->status = 404;

if (isset($doc->WIKIDATA))
{
	$csl = wikidata_to_csl($doc->WIKIDATA);
	
	if ($csl)
	{	
		$style_sheet = StyleSheet::loadStyleSheet($doc->style);
		$citeProc = new CiteProc($style_sheet);
		$doc->citation = $citeProc->render(array($csl), "bibliography");
		$doc->citation = strip_tags($doc->citation);
		$doc->citation = trim(html_entity_decode($doc->citation, ENT_QUOTES | ENT_HTML5, 'UTF-8'));	
	}		
	
	if (isset($doc->citation))
	{	
		$doc->status = 200;
	}
	
}

send_doc($doc);	

?>
