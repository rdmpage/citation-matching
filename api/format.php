<?php

// Format a DOI as a citation string

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;


$doc = null;
$doc = http_post_endpoint(["DOI"]);

if (0)
{
	$doc = new stdclass;
	$doc->DOI = "10.1111/j.1365-2311.1929.tb01417.x";
	$doc->style = 'apa';
}

if (!isset($doc->style))
{
	$doc->style = "apa";
}

$doc->status = 404;

if (isset($doc->DOI))
{
	$mode = 0; // use content negotiation with style sheet

	$parts = explode('/', $doc->DOI);
	$prefix = $parts[0];
	
	switch ($prefix)
	{
		case '10.18942':
			$mode = 1;
			break;
	
		default:
			$mode = 0;
			break;
	}
	

	if ($mode)
	{
		// get CSL-JSON and format locally
		$json = get('https://doi.org/' . $doc->DOI, 'application/vnd.citationstyles.csl+json');
		$csl = json_decode($json);
		if ($csl)
		{
			// fix
			if (!isset($csl->type))
			{
				$csl->type = 'article-journal';
			}
		
			$style_sheet = StyleSheet::loadStyleSheet($doc->style);
			$citeProc = new CiteProc($style_sheet);
			$doc->citation = $citeProc->render(array($csl), "bibliography");
			$doc->citation = strip_tags($doc->citation);
			$doc->citation = trim(html_entity_decode($doc->citation, ENT_QUOTES | ENT_HTML5, 'UTF-8'));	
		}		
	}
	else
	{
		$doc->citation = get('https://doi.org/' . $doc->DOI, 'text/x-bibliography; style=' . $doc->style);
		if (preg_match('/^<!DOCTYPE html/', $doc->citation))
		{
			unset ($doc->citation);
		}
		else
		{
			$doc->citation = trim($doc->citation);
			$doc->citation = preg_replace('/\R/u', ' ', $doc->citation);
			$doc->citation = preg_replace('/\s\s+/u', ' ', $doc->citation);		
		}
	}
	
	if (isset($doc->citation))
	{	
		$doc->status = 200;
	}
}

send_doc($doc);	

?>

