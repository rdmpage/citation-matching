<?php

// Decode Internet Archive URLs
// see also https://github.com/rdmpage/biostor/blob/master/api_url.php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__)  . '/external.php');
require_once (dirname(dirname(__FILE__))  . '/db.php');

$required = array('URL');
$doc = http_post_endpoint($required);

if (0)
{
	$doc = new stdclass;
	
	// named pages
	$doc->URL = 'https://archive.org/stream/journalofbombayn19abomb#page/436/mode/1up';	
	$doc->URL = 'http://www.archive.org/stream/bulletindelasoci1905socie#page/199/mode/1up';
	
	// order in item
	$doc->URL = 'http://www.archive.org/stream/bulletinofsouthe3839sout#page/n24/mode/1up';


	$doc->URL = 'http://www.archive.org/stream/bulletinofsouthe4445sout#page/119/mode/1up';
	$doc->URL = 'http://www.archive.org/stream/bulletindumusumn27musu#page/334/mode/1up';
}

$doc->status = 404;

if (isset($doc->URL))
{

	// labelled page
	if (preg_match('/https?:\/\/(www\.)?archive.org\/stream\/(?<ia>[A-Za-z0-9]+)#page\/(?<page>\d+)(\/mode\/\d+up)?/', $doc->URL, $m))
	{
		// print_r($m);
	
		$ia = $m['ia'];			
		$page = $m['page'];
	
		$doc->BHLITEMID[] = get_bhl_item_from_ia($ia);	
		$doc->BHLPAGEID = bhl_pages_with_number($doc->BHLITEMID[0], $page, false);
	}

	// ordered page
	if (preg_match('/https?:\/\/(www\.)?archive.org\/stream\/(?<ia>[A-Za-z0-9]+)#page\/n(?<page>\d+)(\/mode\/\d+up)?/', $doc->URL, $m))
	{
		// print_r($m);
	
		$ia = $m['ia'];			
		$page = $m['page'];
	
		$doc->BHLITEMID[] = get_bhl_item_from_ia($ia);	
		$doc->BHLPAGEID = bhl_pages_with_number($doc->BHLITEMID[0], $page, true);
	}
	
	if (isset($doc->BHLPAGEID))
	{
		$doc->status = 200;
	}
}

send_doc($doc);	

?>
