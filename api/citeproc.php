<?php

// Format a document using citeproc (document is treated as a CSL-JSON object)

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');

use Seboettg\CiteProc\StyleSheet;
use Seboettg\CiteProc\CiteProc;


$doc = null;
$doc = http_post_endpoint(["title", "style"]);



if (!isset($doc->style))
{
	$doc->style = "apa";
}

$doc->status = 404;

if (isset($doc->title))
{
	$csl = $doc;
	
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
	
	if (isset($doc->citation))
	{	
		$doc->status = 200;
	}
}

send_doc($doc);	

?>

