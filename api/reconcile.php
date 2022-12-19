<?php

// Reconciliation client for multiple endpoints

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/db.php');

$doc = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	$doc = http_get_endpoint(["q"]);
}
else
{
	$doc = http_post_endpoint(["q"]);
}

$doc->status = 404;

if (0)
{
	$doc = new stdclass;
	$doc->q = 'Strand, E. 1912. Ein neueres Werk über afrikanische Bienen kritisch besprochen. Archiv für Naturgeschichte 78: 126-144';
	$doc->q = 'Roth, L. M. 1972. The male genitalia of Blattaria IX. Blaberidae. Gyna spp. (Perisphaeriinae) Phoraspis, Thorax, and Phlebonotus (Epilamprinae). Transactions of the American Entomological Society 98(2):203';
	$doc->status = 404;
}

// reconciliation API(s)

// clean
$doc->q = strip_tags($doc->q);
$doc->q = html_entity_decode($doc->q);

$query = new stdclass;
$key = 'q0';
$query->{$key} = new stdclass;
$query->{$key}->query = $doc->q;
$query->{$key}->limit = 3;

$endpoints = array(
	'wikidata' => 'https://wikicite-search.herokuapp.com/api_reconciliation.php?queries=',
	//'biostor' => 'https://biostor.org/reconcile?queries='
);

foreach ($endpoints as $service => $endpoint)
{
	$url = $endpoint . urlencode(json_encode($query));
	
	//echo $url . "\n";

	$opts = array(
	  CURLOPT_URL =>$url,
	  CURLOPT_FOLLOWLOCATION => TRUE,
	  CURLOPT_RETURNTRANSFER => TRUE
	);

	$ch = curl_init();
	curl_setopt_array($ch, $opts);
	$data = curl_exec($ch);
	$info = curl_getinfo($ch); 
	curl_close($ch);
	
	//echo $data;
	
	if ($data != '')
	{
		$response = json_decode($data);
	
		//print_r($response);
		
		if (isset($response->{$key}->result))
		{
			if (isset($response->{$key}->result[0]))
			{
				if ($response->{$key}->result[0]->match)
				{
					$doc->status = 200;
					$doc->score = $response->{$key}->result[0]->score;
					
					switch ($service)
					{
						case 'biostor':
							$doc->BIOSTOR = $response->{$key}->result[0]->id;
							break;

						case 'wikidata':
							$doc->WIKIDATA = $response->{$key}->result[0]->id;
							break;

						default:
							break;
					}
					
				}
			}
		
		}
	}
	
	if ($doc->status == 200)		
	{
		break;
	}
}

send_doc($doc);	

?>
