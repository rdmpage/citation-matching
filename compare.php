<?php

// string cleaning and comparison

require_once(dirname(__FILE__) . '/lcs.php');

mb_internal_encoding("UTF-8");
setlocale(LC_ALL, 0);
date_default_timezone_set('UTC');

define ('WHITESPACE_CHARS', ' \f\n\r\t\x{00a0}\x{0020}\x{1680}\x{180e}\x{2028}\x{2029}\x{2000}\x{2001}\x{2002}\x{2003}\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200a}\x{202f}\x{205f}\x{3000}');
define ('PUNCTUATION_CHARS', '\?\!\.\-—,\(\)\[\]:;«»\'\&"\`\´„”“”‘’');


//----------------------------------------------------------------------------------------
// https://stackoverflow.com/a/2759179
function unaccent($string)
{
    $string = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
    $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $string;
}

//----------------------------------------------------------------------------------------
// https://gist.github.com/keithmorris/4155220
function removeCommonWords($input){
 
 	// EEEEEEK Stop words
	$commonWords = array('and', 'der', 'des', 'die', 'do', 'et', 'fur', 'in', 'of', 'the', 'und');
 
	return preg_replace('/\b('.implode('|',$commonWords).')\b/i','',$input);
}


//----------------------------------------------------------------------------------------
// Clean up text so that we have single spaces between text, 
// see https://github.com/readmill/API/wiki/Highlight-locators
function clean_text($text)
{	
	$text = strip_tags($text);
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	
	$text = preg_replace('/\.(\p{Lu}|\p{L})/u', '. $1', $text);
	
	$text = preg_replace('/[' . WHITESPACE_CHARS . ']+/u', ' ', $text);
	
	return $text;
}

//----------------------------------------------------------------------------------------
// Normalise text by cleaning it and removing punctuation
function normalise_text($text)
{
	// clean
	$text = clean_text($text);
	$text = unaccent($text);
	
	// remove punctuation
	//$text = preg_replace('/[' . PUNCTUATION_CHARS . ']+/u', '', $text);
	$text = preg_replace('/[^a-z0-9 ]/i', '', $text);
	
	// lowercase
	$text = mb_convert_case($text, MB_CASE_LOWER);
	
	return $text;
}

//----------------------------------------------------------------------------------------
// trim a string to a set length, for example some comparison methods fail if string is too
// long
function shorten_text($text, $length = 250) 
{
	if (mb_strlen($text) > $length)
	{
		$text = mb_substr($text, 0, $length - 1);
	}

	return $text;
}

//----------------------------------------------------------------------------------------
// string identity
function compare_simple($text1, $text2, $debug = false)
{
	$text1 = normalise_text($text1);
	$text2 = normalise_text($text2);
	
	$result = new stdclass;
	$result->strings = [$text1, $text2];
	$result->name = 'simple';
	$result->value = strcmp($text1, $text2);
	$result->normalised = ($result->value === 0) ? 1 : 0;
	
	return $result;
}

//----------------------------------------------------------------------------------------
// Get longest common subsequence for two strings
// Return value is array of minimum and maximum rations of subsequence length w.r.t. input strings
// Idea is that we can use these two numbers to get some sense of whether the match is spurious or not.
function compare_common_subsequence($text1, $text2, $debug = false)
{
	$text1 = normalise_text($text1);
	$text2 = normalise_text($text2);

	$lcs = new LongestCommonSequence($text1, $text2);

	$d = $lcs->score();
	
	if ($debug)
	{
		echo $lcs->show_alignment();
	}
	
	$length1 = strlen($text1);
	$length2 = strlen($text2);
	
	$result = new stdclass;
	$result->strings = [$text1, $text2];
	$result->name = 'subsequence';
	$result->value = $d;
	$result->lengths = [$length1, $length2];
	$result->normalised = [$d / min($length1, $length2), $d / max($length1, $length2)];
	sort($result->normalised);
	
	return $result;
}

//----------------------------------------------------------------------------------------
function compare_levenshtein($text1, $text2, $debug = false)
{
	$text1 = normalise_text($text1);
	$text2 = normalise_text($text2);
	
	$text1 = shorten_text($text1);
	$text2 = shorten_text($text2);

	$d = levenshtein($text1, $text2);
	
	$length1 = strlen($text1);
	$length2 = strlen($text2);
	
	$result = new stdclass;
	$result->strings = [$text1, $text2];
	$result->name = 'levenshtein';
	$result->value = $d;
	$result->lengths = [$length1, $length2];
	$result->normalised = 1 - $d / max($length1, $length2);
	
		
	return $result;
}

//----------------------------------------------------------------------------------------
// treat string as bag of words
function bag_of_words($text)
{
	$text = clean_text($text);
	$words = explode(' ', $text);
	
	asort($words);
	
	return join(' ', $words);
}


//----------------------------------------------------------------------------------------

if (0)
{
	$text = 'Anales del Jardin Botánico de Madrid';
	//$text = '[[Anales del Jardin Botánico de Madrid]]';
	//$text = 'Göteb. Kgl. Vetensk. och Vitterh.-Samh. Handlingar. Fjärde följden';
	//$text = '[[Flore des Serres et des Jardins de l’Europe]]';
	$text = 'Actes du premièrs Congrés International de Spéléologie, Paris';
	$text = '2000a';
	
	$text = 'Bull. Mus. Natl. d\'Hist. Nat., Paris, (sér. 2)';
	$text = 'Bulletin de la Musee d\'Histoire Naturelle de Paris, 2e sér.';
	
	echo "       Raw $text\n";
	echo "   Cleaned " . clean_text($text) . "\n";
	echo "Normalised " . normalise_text($text) . "\n";


}

if (0)
{
	$pairs = array(
	'Annu Conserv Jard Bot Geneve',
	'Annuaire du Conservatoire et du Jardin Botaniques de Geneve'
	);	

	
	$pairs = array(
	'Bull. Mus. Natl. d\'Hist. Nat., Paris, (sér. 2)',
	'Bulletin de la Musee d\'Histoire Naturelle de Paris, 2e sér.'
	);	
	
	
	//$pairs = ['Acta Societatis Scientiarum Fennica', 'Acta Soc. Sci. Fennicae'];
	
	
	// people
	//$pairs = ['JOSÉ ESTEBAN JIMÉNEZ', 'José Esteban Jiménez'];

	//$pairs = ['FRED R. BARRIE', 'Fred Rogers Barrie'];

	//$pairs = ['Henrik Æ. Pedersen', 'Henrik Aerenlund Pedersen'];
	
	$pairs = ['Kaj Vollesen', 'Kaj Børge Vollesen'];
	$pairs = ['Ko Wanchang', 'Wan Chang Ko'];
	
	$pairs = ['A systematic study on Chinese species of the ant genus <i>Oligomyrmex</i> Mayr (Hymenoptera: Formicidae)',
			'A systematic study on Chinese species of the ant genus Oligomyrmex Mayr (Hymenoptera: Formicidae)'];

	$pairs = ['Flore (Pteridophyta et Spermatophyta) des zones humides du Maroc Méditérranéen: Inventaire et écologie',
	'Flore (&quot;Pteridophyta&quot; et &quot;Spermatophyta&quot;) des zones humides du Maroc Méditérranéen'];
	
	$pairs = [
	'Smith, J.J. (1914) Neue Orchideen des Malaiischen Archipels. VII. Bulletin du Jardin botanique de Buitenzorg Sér. 2, 13: 1–52.',
	'Smith, J.J. (1914b) Neue Orchideen des malaiischen Archipels VII. Bulletin du Jardin Botanique de Buitenzorg, sér. 2, 13: 1–52.'
	];
	
	$pairs = ['JOSÉ ESTEBAN JIMÉNEZ', 'José Esteban Jiménez'];
	
	$pairs = ['K.I. Goebel', 'K.I.E. Goebel'];
	
	$pairs = ['Nascimento, J.G.A.do', 'J.G.A. do Nascimento'];
	
	print_r($pairs);
	
	$d = compare_common_subsequence($pairs[0], $pairs[1], true);	
	print_r($d);
	
	$d = compare_levenshtein($pairs[0], $pairs[1]);	
	print_r($d);

	$d = compare_simple($pairs[0], $pairs[1]);	
	print_r($d);
	
	
	echo "---\n";
	$pairs[0] = bag_of_words($pairs[0]);
	$pairs[1] = bag_of_words($pairs[1]);
	
	print_r($pairs);
	
	
	$d = compare_common_subsequence($pairs[0], $pairs[1], true);	
	print_r($d);

}


?>