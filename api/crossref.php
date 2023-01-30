<?php

// Match using CrossRef API

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)). '/compare.php');

$debug = true;
$debug = false;

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

if ($debug)
{
	$doc = new stdclass;
	$doc->q = 'DOUGHTY, P., KEALLEY, L., & MELVILLE, J. (2012). Taxonomic assessment of Diporiphora (Reptilia: Agamidae) dragon lizards from the western arid zone of Australia. Zootaxa, 3518(1), 1';
	$doc->q = 'Untangling the trees: Revision of the Calumma nasutum complex (Squamata: Chamaeleonidae). Vertebrate Zoology 70: 23-59';
	$doc->q = 'Neang, Thy; Somaly Chan, Nikolay A. Poyarkov, Jr.. (2018) A new species of smooth skink (Squamata: Scincidae: Scincella) from Cambodia. Zoological Research, DOI: 10.24272/j.issn.2095-8137.2018.008';
	
	$doc->q = '2021 Twenty-eight new species of Trigonopterus Fauvel (Coleoptera, Curculionidae) from Central Sulawesi. ZooKeys, 1065, Oct 22 2021: 29-79.  42 ';
	$doc->status = 404;
}

$url = 'https://api.crossref.org/works?query=' . urlencode($doc->q) . '&filter=type%3Ajournal-article';

$json = get($url);

//echo $json;

$obj = json_decode($json);



if ($obj)
{	
	$keys = ['author', 'issued', 'title', 'container-title', 'volume', 'issue', 'page', 'DOI', 'ISSN'];

	$n = min(3, count($obj->message->items));
	
	$best_score = 0;
	$best_hit = null;	
	
	for ($i = 0; $i < $n; $i++)
	{
		// build a string to use for double checking match
		$terms = array();
		
		$csl = $obj->message->items[$i];
		
		foreach ($keys as $k)
		{
			if (isset($csl->$k))
			{
				switch ($k)
				{
					case 'author':
						// only add authors if we think query string has them (ION won't, for example)
						$have_authors = false;
						if (preg_match('/^(?<prefix>[^\d]+)(\b[0-9]{4}[a-z]?\b)/', $doc->q, $m))
						{
							$have_authors = strlen($m['prefix']) > 3;						
						}
						if ($have_authors)
						{
							foreach ($csl->$k as $author)
							{
								$author_parts = array();
								if (isset($author->family))
								{
									$author_parts[] = $author->family;
								}
								if (isset($author->given))
								{
									$author_parts[] = $author->given;
								}
								$terms[] = join(', ', $author_parts);
							}
						}
						break;
						
					case 'issued':
						$terms[] = $csl->$k->{'date-parts'}[0][0];
						break;
						
					case 'DOI':
						// eat(?)
						if (preg_match('/10\.\d+/', $csl->$k))
						{
							$terms[] = $csl->$k;
						}
						break;
						
					case 'ISSN':
						// eat(?)
						break;
						
					default:
						if (is_array($csl->$k))
						{
							$terms[] = html_entity_decode($csl->$k[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
						}
						else
						{
							$terms[] = html_entity_decode($csl->$k, ENT_QUOTES | ENT_HTML5, 'UTF-8');
						}
						break;
				
				}
			}
		
		}
		
		//print_r($terms);
		
		$result_string = join(' ', $terms);
		
		// compare
		if ($debug)
		{
			echo "\n";
			$result = compare_common_subsequence($result_string, $doc->q, true);
			echo "\n";
		}
		else
		{
			$result = compare_common_subsequence($result_string, $doc->q, false);						
		}

		// echo "-- [" . $result->normalised[0] . ', ' . $result->normalised[1] ."]\n";

		$matched = false;

		if ($result->normalised[1] > 0.80)
		{
			// one string is almost an exact substring of the other
			if ($result->normalised[0] > 0.75)
			{
				// and the shorter string matches a good chunk of the bigger string
				$matched = true;	
			}
		}
		
		// avoid Zootaxa over hitting (Zootaxa has so many articles it's posisble that we 
		// match a Zootaxa article by mistake)		
		if ($matched)
		{
			if (preg_match('/zootaxa/i', $result_string) && !preg_match('/zootaxa/i', $doc->q))
			{
				$match = false;	
			}
		}

		if ($matched)
		{
			if ($result->normalised[1] > $best_score)
			{
				$best_score = $result->normalised[1];
				$best_hit = $csl;
			}
		
		}
	
	}
	
	if ($best_hit)
	{
		$doc->status = 200;
		foreach ($keys as $k)
		{
			if (isset($best_hit->$k))
			{
				$doc->{$k} = $best_hit->$k;
			}
		}
	}

	if ($debug)
	{
		print_r($doc);
	}
	
}




send_doc($doc);	



?>
