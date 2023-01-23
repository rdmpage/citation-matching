<?php

// Search for article using CrossRef OpenURL interface

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utilities.php');

use Sunra\PhpSimple\HtmlDomParser;

$doc = null;
$doc = http_post_endpoint(["container-title", "volume", "page"]);

if (0)
{
	$json = '{
	"container-title": "New Zealand J. Zool.",
    "volume": "14",
    "page": 593,
    "issued": {
    	"date-parts" : [ [1987]]
    }
	
	}';
	
	$doc = json_decode($json);
}

$doc->status = 404;


// build OpenURL parameters
$keys = array("container-title", "ISSN", "volume", "page", "issued");

$parameters = array();
foreach ($keys as $k)
{
	if (isset($doc->{$k}))
	{
		switch ($k)
		{
			case 'container-title':
				$parameters['title'] = $doc->{$k};
				break;

			case 'ISSN':
				$parameters['issn'] = $doc->{$k}[0];
				break;
				
			case 'volume':
				$parameters['volume'] = $doc->{$k};
				break;

			case 'issued':
				if (isset($doc->{$k}->{'date-parts'}))
				{
					$parameters['date'] = $doc->{$k}->{'date-parts'}[0][0];
				}
				break;

			case 'page':
				$pages = preg_split("/[-|—]/u", $doc->{$k});
				$parameters['spage'] = $pages[0];
				break;
				
			default:
				break;
		
		}		
	}
}

//print_r($parameters);
//exit();

$openurl = http_build_query($parameters);

$doc->openurl = $openurl;

//$openurl = str_replace('&amp;', '&', $openurl);

$url = 'http://www.crossref.org/openurl?pid=' . $config['CROSSREF_API_KEY'] . '&' . $openurl .  '&noredirect=true&format=unixref';
	
$opts = array(
  CURLOPT_URL =>$url,
  CURLOPT_FOLLOWLOCATION => TRUE,
  CURLOPT_RETURNTRANSFER => TRUE
);

//echo $url . "\n";

$ch = curl_init();
curl_setopt_array($ch, $opts);
$data = curl_exec($ch);
$info = curl_getinfo($ch); 
curl_close($ch);

//echo $data;

if ($data != '')
{
	$dom= new DOMDocument;
	$dom->loadXML($data);
	$xpath = new DOMXPath($dom);
	
	$xpath_query = '//journal_article[@publication_type="full_text"]/doi_data/doi';
	$xpath_query = '//journal_article/doi_data/doi';
	$nodeCollection = $xpath->query ($xpath_query);
	
	foreach($nodeCollection as $node)
	{
		$doc->DOI = strtolower($node->firstChild->nodeValue);
	}

}

if (isset($doc->DOI))
{
	$doc->status = 200;
}

send_doc($doc);	

?>
