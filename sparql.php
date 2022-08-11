<?php

// Queries using triplestore

error_reporting(E_ALL);

require_once('vendor/autoload.php');
require_once ('config.inc.php');

use ML\JsonLD\JsonLD;
use ML\JsonLD\NQuads;

	
//----------------------------------------------------------------------------------------
function post_sparql($url, $data = '', $accept = 'application/rdf+xml')
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	// data needs to be a string
	if ($data != '')
	{
		if (gettype($data) != 'string')
		{
			$data = json_encode($data);
		}	
	}	
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		
	$headers = array();
	
	$headers[] = "Content-type: application/sparql-query";
	
	if ($accept != '')
	{
		$headers[] = "Accept: " . $accept;
	}
	
	if (count($headers) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	
	$response = curl_exec($ch);
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}	

//----------------------------------------------------------------------------------------
function do_sparql_query($query, $accept)
{
	global $config;
	
	$response = post_sparql($config['sparql_endpoint'], $query, $accept);
	
	return $response;
	
}

//----------------------------------------------------------------------------------------
// CONSTRUCT to get text for a page (only works if we add text to triple store... 
// which means triple store will be uuuge)
function get_page_text($PageID)
{
	$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	CONSTRUCT
	{
	  ?page schema:text ?text .
	}
	WHERE
	{
	  VALUES ?page { <https://www.biodiversitylibrary.org/page/' . $PageID . '> } .
	  ?page rdf:type ?type .
	  ?page schema:text ?text .
	}';

	$triples = do_sparql_query($query, 'application/n-triples');

	// convert to JSON-LD so we can work with it
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";

	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();

	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);

	$obj = JsonLD::compact($doc, $context);
	
	if (is_array($obj->text))
	{
		$text = $obj->text[0];
	}
	else
	{
		$text = $obj->text;
	}
	
	return $text;
}


//----------------------------------------------------------------------------------------
// CONSTRUCT to get page from title, volume, and page
function get_page_from_triple($TitleID, $volumeNumber, $pageNumber)
{
	$result = array();

	$query = 'PREFIX schema: <http://schema.org/>
	PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
	PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
	PREFIX fabio: <http://purl.org/spar/fabio/>
	PREFIX bibo: <http://purl.org/ontology/bibo/>
	
	CONSTRUCT
	{
	  ?page schema:name ?pageName .
	  ?page rdf:type fabio:Page .
	  
	  ?page schema:isPartOf ?part .
	  ?part schema:name ?name .
	  ?part bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?title { <https://www.biodiversitylibrary.org/bibliography/' . $TitleID . '> } .
	  VALUES ?volumeNumber { "' . $volumeNumber .'" } .
	  VALUES ?pageName { "' . $pageNumber . '" } .
	  
	  ?volume schema:isPartOf ?title .
	  ?volume rdf:type schema:PublicationVolume .
	  ?volume schema:volumeNumber ?volumeNumber .
  
	  ?page schema:isPartOf ?volume .
	  ?page rdf:type fabio:Page .
	  ?page schema:name ?pageName .
	  
	  OPTIONAL {
		?page schema:isPartOf ?part .
		?part rdf:type schema:ScholarlyArticle .
		?part schema:name ?name .
		OPTIONAL {
		  ?part bibo:doi ?doi .
		}
	  }	  
	}';
	
	//echo $query . "\n";
	
	$triples = do_sparql_query($query, 'application/n-triples');

	if ($triples == "")
	{
		return $result;
	}

	// convert to JSON-LD so we can work with it
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
	$context->bibo = "http://purl.org/ontology/bibo/";
	$context->fabio = "http://purl.org/spar/fabio/";
	
	$context->doi = "bibo:doi";
	$context->page = "fabio:Page";
	
	// isPartOf is array
	$isPartOf = new stdclass;
	$isPartOf->{'@id'} = "isPartOf";
	$isPartOf->{'@container'} = "@set";
	$context->isPartOf = $isPartOf;
	
	// id
	$context->id = '@id';

	// type
	$context->type = '@type';	
	
	// doi

	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();

	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);
	
	$frame = (object)array(
			'@context' => $context,
			'@type' => 'http://purl.org/spar/fabio/Page'
		);
	$obj = JsonLD::frame($doc, $frame);
	
	if (is_object($obj->{"@graph"}))
	{
		$result = array($obj->{"@graph"});
	}
	else
	{
		$result = $obj->{"@graph"};
	}
	return $result;
}


//----------------------------------------------------------------------------------------
// CONSTRUCT to get part(s) BHL page is part of, that is, does page occur in any articles?
function get_part_from_bhl_page($PageID)
{
	$result = array();

	$query = 'PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
PREFIX dc: <http://purl.org/dc/elements/1.1/>
	CONSTRUCT
	{
	  ?page schema:name ?pageName .
      ?page rdf:type ?type .
	  
	  ?page schema:isPartOf ?part .
	  ?part dc:title ?name .
	  ?part bibo:doi ?doi .
	}
	WHERE
	{
	  VALUES ?page { <https://www.biodiversitylibrary.org/page/' . $PageID . '> } .
      VALUES ?type { fabio:Page }
	
		?page rdf:type ?type .
		?page schema:name ?pageName .
  
  		OPTIONAL {
			?page schema:isPartOf ?part .
			?part rdf:type schema:ScholarlyArticle .
			?part schema:name ?name .
			OPTIONAL {
			  ?part bibo:doi ?doi .
			}
		}
	}';
	
	$triples = do_sparql_query($query, 'application/n-triples');
	
	if ($triples == "")
	{
		return $result;
	}
	
	// convert to JSON-LD so we can work with it
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
	$context->bibo = "http://purl.org/ontology/bibo/";
	$context->fabio = "http://purl.org/spar/fabio/";
	$context->dc = "http://purl.org/dc/elements/1.1/";
	
	$context->DOI = "bibo:doi";
	$context->page = "fabio:Page";
	
	// isPartOf is array
	$isPartOf = new stdclass;
	$isPartOf->{'@id'} = "isPartOf";
	$isPartOf->{'@container'} = "@set";
	$context->isPartOf = $isPartOf;
	
	// id
	$context->id = '@id';

	// type
	$context->type = '@type';	
	
	// title
	$context->title = 'dc:title';
	
	// doi

	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();

	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);
	
	$frame = (object)array(
			'@context' => $context,
			'@type' => 'http://purl.org/spar/fabio/Page'
		);
	$obj = JsonLD::frame($doc, $frame);
	
	if (is_object($obj->{"@graph"}))
	{
		$result = array($obj->{"@graph"});
	}
	else
	{
		$result = $obj->{"@graph"};
	}
	
	return $result;
}

//----------------------------------------------------------------------------------------
// CONSTRUCT to get page from title, volume, and page
function get_page_from_triple_issn($issn, $volumeNumber, $pageNumber)
{
	$result = array();

	$query = 'PREFIX schema: <http://schema.org/>
PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX fabio: <http://purl.org/spar/fabio/>
PREFIX bibo: <http://purl.org/ontology/bibo/>
CONSTRUCT
{
  ?page schema:name ?pageName .
  ?page rdf:type fabio:Page .
  ?page schema:text ?text .
  
  ?page schema:isPartOf ?work .
  ?work schema:name ?name .
  ?work bibo:doi ?doi .
}
WHERE
{ 
	VALUES ?issn { "' . $issn . '" } .
	VALUES ?volumeNumber { "' . $volumeNumber . '" } .
	VALUES ?pageName { "' . $pageNumber . '" } .

	?container schema:issn $issn .

	?volume schema:isPartOf ?container .
	?volume a schema:PublicationVolume .
	?volume schema:volumeNumber ?volumeNumber .

	?work schema:isPartOf ?volume .
	?work a schema:ScholarlyArticle .
	?work schema:name ?name .
	
	OPTIONAL {
		?work bibo:doi ?doi .
	}	

	?page schema:isPartOf ?work .
	?page a fabio:Page .
	?page schema:name ?pageName .

	?page schema:text ?text .	  
}

';
	
	//echo $query . "\n";
	
	$triples = do_sparql_query($query, 'application/n-triples');

	if ($triples == "")
	{
		return $result;
	}

	// convert to JSON-LD so we can work with it
	$context = new stdclass;
	$context->{'@vocab'} = 'http://schema.org/';
	$context->rdf =  "http://www.w3.org/1999/02/22-rdf-syntax-ns#";
	$context->bibo = "http://purl.org/ontology/bibo/";
	$context->fabio = "http://purl.org/spar/fabio/";
	
	$context->doi = "bibo:doi";
	$context->page = "fabio:Page";
	
	// isPartOf is array
	$isPartOf = new stdclass;
	$isPartOf->{'@id'} = "isPartOf";
	$isPartOf->{'@container'} = "@set";
	$context->isPartOf = $isPartOf;
	
	// id
	$context->id = '@id';

	// type
	$context->type = '@type';	
	
	// doi

	// Use same libary as EasyRDF but access directly to output ordered list of authors
	$nquads = new NQuads();

	// And parse them again to a JSON-LD document
	$quads = $nquads->parse($triples);		
	$doc = JsonLD::fromRdf($quads);
	
	$frame = (object)array(
			'@context' => $context,
			'@type' => 'http://purl.org/spar/fabio/Page'
		);
	$obj = JsonLD::frame($doc, $frame);
	
	if (is_object($obj->{"@graph"}))
	{
		$result = array($obj->{"@graph"});
	}
	else
	{
		$result = $obj->{"@graph"};
	}
	return $result;
}


//----------------------------------------------------------------------------------------

// "tests"

if (0)
{
	$text = get_page_text(14779340);
	echo $text;
}

/*

$result = get_page_from_triple(11516, 1914, 269);

print_r($result);


$result = get_page_from_triple(11516, 1922, 102);

print_r($result);

$result = get_part_from_bhl_page(14788065);

print_r($result);

*/

if (0)
{
	$result = get_page_from_triple(7414, "v.22 (1913)", 162);

	print_r($result);
}

if (0)
{
	$result = get_part_from_bhl_page(48431077);

	print_r($result);
}

if (0)
{
	$result = get_page_from_triple_issn('1225-0104', '11', '21');
	print_r($result);
}



?>
