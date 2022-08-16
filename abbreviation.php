<?php

require_once (dirname(__FILE__) . '/config.inc.php');
require_once (dirname(__FILE__) . '/database/sqlite.php');

//----------------------------------------------------------------------------------------
function get_abbreviation($term)
{
	$abbreviation = $term; // default is to not abbreviate

	// Our queries may modify the search terms
	$query_term = $term;	
	
	$prefix = '';
	$suffix = '';
	
	if (preg_match('/^([\(])(.*)$/', $query_term, $m))
	{
		$prefix 	= $m[1];
		$query_term = $m[2];
	}
	
	if (preg_match('/^(.*)([\)])[\.]?$/', $query_term, $m))
	{
		$suffix 	= $m[2];
		$query_term = $m[1];
	}	
	
	$query_length = mb_strlen($query_term);
	
	// Do we want the term in title case?
	$is_title_case = preg_match('/^\p{Lu}/u', $query_term);
	
	$done = false;
	
	$result = array();
	while (!$done)
	{
		// exact match
		$sql = 'SELECT * FROM ltwa20210702 WHERE words="' . $query_term . '" COLLATE NOCASE;';
	
		$result = do_query($sql);
	
		// did we get an exact match?
		if (count($result) == 1)
		{
			// Yes! we are done
			$done = true;
		}
	
		// Nope!
		if (!$done)
		{
			// Are we still oilely to get a match?
			if ($query_length < 5)
			{
				// Nope!
				
				// if we don't have an exact match for a short string then assume no match
				$done = true;
			}
			else
			{
				// Maybe?
				
				// if we don't have match for a longer string, truncate and go again
				$query_term = mb_substr($query_term, 0, $query_length) . '-';
				$sql = 'SELECT * FROM ltwa20210702 WHERE words="' . $query_term . '" COLLATE NOCASE;';
		
				$result = do_query($sql);
		
				if (count($result) == 1)
				{
					$done = true;
				}
				
				$query_length--;

			}
		}
	}

	//print_r($result);

	if (count($result) == 1)
	{
		// handle case where abbreviation is not applicable
		if ($result[0]->abbreviations == 'n.a.')
		{
			$result[0]->abbreviations = $query_term;
		}
	
		// if original term is in title case make sure abbreviation is as well
		if ($is_title_case)
		{
			$result[0]->abbreviations = mb_convert_case($result[0]->abbreviations, MB_CASE_TITLE);
		}
		
		$abbreviation = $result[0]->abbreviations;
		
		// did original term have a prefix e.g., "(" or a suffix? If so, restore them
		if ($prefix != '')
		{
			$abbreviation = $prefix . $abbreviation;
		}
		
		if ($suffix != '')
		{
			$abbreviation = $abbreviation . $suffix;
		}		
		
	}
	
	return $abbreviation;
}

//----------------------------------------------------------------------------------------
function abbreviated_title($text, $force = false)
{
	global $config;
	
	// get cache
	if (!file_exists($config['abbreviation_cache']))
	{
		$cache = new stdclass;
		file_put_contents($config['abbreviation_cache'], json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	}	
	$json = file_get_contents($config['abbreviation_cache']);
	$cache = json_decode($json);
	
	$original_text = $text;
	
	// if cached return abbreviation
	if (isset($cache->{$original_text}) && !$force)
	{		
		$abbreviated = $cache->{$original_text};
		return $abbreviated;
	}

	// if forcing then empty the cached version
	if (isset($cache->{$original_text}) && $force)
	{		
		unset($cache->{$original_text});
	}
	
	// clean 
	$text = preg_replace('/\p{L}\'(\p{Lu})/u', '$1', $text);	
	
	// multiword phrases that have their own abbreviations, so don't split those
	$text = str_replace('New South Wales', 	'New•South•Wales', $text);	
	$text = str_replace('New Zealand', 		'New•Zealand', $text);
	$text = str_replace('South Africa', 	'South•Africa', $text);
	$text = str_replace('United States', 	'United•States', $text);
	
	$text_array = explode(' ', $text);
	
	// remove special characters used to protect phrases
	foreach ($text_array as &$text_element)
	{
		$text_element = str_replace('•', ' ', $text_element);	
	}

	// remove stop words
	$stopwords = array('and', 'das', 'de', 'della', 'der', 'die', 'for', 'fuer', 'für', 'la', 'of', 'the');
	$text_array = array_udiff($text_array, $stopwords, 'strcasecmp');

	// array of abbreviated words
	$abbreviation = array();
	foreach ($text_array as $k => $word)
	{	
		$abbreviation[] = get_abbreviation($word);
	}
	
	$abbreviated = join(' ', $abbreviation);
	
	$cache->{$original_text} = $abbreviated;
	file_put_contents($config['abbreviation_cache'], json_encode($cache, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));			
	
	return $abbreviated;
}


//----------------------------------------------------------------------------------------
// tests

if (1)
{
	$strings = array(
	/*
	'Annals and Magazine of Natural History',
	'Acta Entomologica Sinica',
	'Zootaxa',
	'Acta Botanica Mexicana',
	'Edinburgh Journal of Botany',
	'Lankesteriana',
	'Deutsche Entomologische Zeitschrift',
	'Zoosystematica Rossica',
	'Journal of Ichthyology',
	'Der Zoologische Garten',
	'Annales Zoologici',
	'Bulletin of the British Ornithologists\' Club',
	*/

	/*
	'Exotic Microlepidoptera',

	'Bulletin of the British Museum (Natural History). Geology. Supplement',

	'Bulletin of Zoological Nomenclature',
	'Bulletin of the British Museum (Natural History) Zoology',
	'Occasional Papers of the Museum of Natural History University of Kansas',
	'Proceedings of the United States National Museum',
	'Journal of Hymenoptera Research',

	'Proceedings of the Entomological Society, Washington',
	'Proceedings of the California Academy of Sciences',
	'The Bulletin of Zoological Nomenclature',
	'Proceedings of the Linnean Society of New South Wales',
	*/

	'Zeitschrift Fuer Saeugetierkunde',
	'Zeitschrift für Säugetierkunde',
	'Zeitschrift fuer Wissenschaftliche Insektenbiologie Berlin',

	'Proceedings of the Academy of Natural Sciences of Philadelphia',

	);
	
	$strings = array(
	'Annals of The South African Museum',
	'Entomologisk Tidskrift',
	'Bulletin of Zoological Nomenclature',
	'Bulletin of the British Museum (Natural History) Zoology',
	'Occasional Papers of the Museum of Natural History University of Kansas',
	'Proceedings of the United States National Museum',
	'Journal of Hymenoptera Research',
	'Zeitschrift Fuer Saeugetierkunde',
	'Zeitschrift für Säugetierkunde',
	'Proceedings of the Academy of Natural Sciences of Philadelphia',
	
	
	);
	
	$strings=array(
	'Annals of the Transvaal Museum',
	'Archiv fuer Naturgeschichte',
	'Arkiv for Zoologi Stockholm',
	'Australian Journal of Zoology',
	'Beitraege zur Entomologie',
	'Bull Inst Sci nat Belg Brussels',
	'Bull. zool. Nom.',
	'Bulletin de la Societe Entomologique de France',
	'Zoological Research',
	'Zoologicheskii Zhurnal',
	'Zoologische Mededeelingen Leiden',
	);
	
	$strings=array(
	'Zoologische Mededeelingen Leiden', // table may be wrong or I misunderstood
	);

	$strings=array(
	'Tropical Lepidoptera', 
	);
	

	foreach ($strings as $title)
	{
		$abbreviated = abbreviated_title($title, true);
		echo $title  . ' = ' . $abbreviated . "\n";
	}
}

?>

