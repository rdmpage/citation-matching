<?php

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/php-approximate-search/approximate-search.php');

define ('FLANKING_LENGTH', 32);

//----------------------------------------------------------------------------------------
// https://kvz.io/reverse-a-multibyte-string-in-php.html
function mb_strrev ($string, $encoding = null) {
	if ($encoding === null) {
		$encoding = mb_detect_encoding($string);
	}

	$length   = mb_strlen($string, $encoding);
	$reversed = '';
	while ($length-- > 0) {
		$reversed .= mb_substr($string, $length, 1, $encoding);
	}

	return $reversed;
}

//----------------------------------------------------------------------------------------
// Get longest common subsequence of query in original text
// $X is query, $Y is target. Return start and end positions of match in target string
function compare($needle, $haystack)
{
	$obj = new stdclass;
	$obj->range = array();
	$obj->d = -1;
	
	$obj->needle = $needle;
	$obj->haystack = $haystack;
	
	$obj->left 	= '';
	$obj->right = '';
	$obj->bars 	= '';

	// compare two strings 
	$C = array();
	
	$X = $needle;
	$Y = $haystack;

	$m = mb_strlen($X);
	$n = mb_strlen($Y);
	
	// initialise start and end positions of match
	$obj->range= array($n, 0);	

	for ($i = 0; $i <= $m; $i++)
	{
		$C[$i][0] = 0;
	}
	for ($j = 0; $j <= $n; $j++)
	{
		$C[0][$j] = 0;
	}

	for ($i = 1; $i <= $m; $i++)
	{
		for ($j = 1; $j <= $n; $j++)
		{
			if (mb_substr($X, $i - 1, 1) == mb_substr($Y, $j - 1, 1))
			{
				$C[$i][$j] = $C[$i-1][$j-1] + 1;
			}
			else
			{
				$C[$i][$j] = max($C[$i][$j-1], $C[$i-1][$j]);
			}
		}
	}
	
	// score
	$obj->d = $C[$m][$n];
	
	/*
	echo "   ";
	for ($j = 1; $j <= $n; $j++)
	{
		echo str_pad(ord(mb_substr($Y, $j - 1, 1)) , 3, 'x', STR_PAD_LEFT);
	}
	echo "\n";
	for ($i = 1; $i <= $m; $i++)
	{
		echo mb_substr($X, $i - 1, 1) . ' ';
	
		for ($j = 1; $j <= $n; $j++)
		{
			echo str_pad($C[$i][$j], 3, ' ', STR_PAD_LEFT);
		}
		echo "\n";
	}
	*/
	
	// alignment (this is done in reverse order)
	// we use the alignment to get start and end positions of the match in haystack
		
	$i = $m;
	$j = $n;
	
	// Because we are getting the ongest common subsequence irrespective of gaps, we
	// can sometimes get "ugly" alignments such as 
	// abascantu--s
    // |||||||||  |
    // abascantus s
	// To avoid these we go left in the score matrix until the scores decrease.
	
	while ($C[$i][$j] == $C[$i][$j-1])
	{
		$j--;
	}
	
	while ($i > 0 && $j > 0)
	{
		if (mb_substr($X, $i - 1, 1) == mb_substr($Y, $j - 1, 1))
		{
			$obj->left .= mb_substr($X, $i - 1, 1);
			$obj->bars .= '|';
			$obj->right .= mb_substr($Y, $j - 1, 1);
			
			// update range
			$obj->range[1] = max($obj->range[1], $j);
			$obj->range[0] = min($obj->range[0], $j - 1);
			
			$i--;
			$j--;
		}
		else 
		if (($j > 0) and ($i == 0 or $C[$i][$j-1] >= $C[$i-1][$j]))
		{
			$obj->left .= '-';
			$obj->bars .= ' ';
			$obj->right .= mb_substr($Y, $j - 1, 1);
			
			$j--;
		
		}
		else 
		if (($i > 0) and ($j == 0 or $C[$i][$j-1] < $C[$i-1][$j]))
		{
			$obj->left .= mb_substr($X, $i - 1, 1);
			$obj->bars .= ' ';
			$obj->right .= '-';
			
			$i--;
		}
	}
	
	$obj->left = mb_strrev($obj->left);
	$obj->bars = mb_strrev($obj->bars);
	$obj->right = mb_strrev($obj->right);
	
	$obj->alignment = "\n" . join("\n", array($obj->left, $obj->bars, $obj->right));
	
	return $obj;	
}	

//----------------------------------------------------------------------------------------
function find_in_text($needle, $haystack, $case_insensitive = false, $max_error = 1)
{
	$output_html = true;
	$output_html = false;
	
	$result = new stdclass;

	$query = $needle;
	$text = $haystack;
	
	$original_query = $query;
	
	if ($case_insensitive)
	{
		$text = mb_strtolower($text);
		$query = mb_strtolower($query);
	}

	// Search using approximate search
	$search = new Approximate_Search($query, $max_error);
	if ( $search->too_short_err )
	{
		 $result->message = "Unable to search for \"" . addcslashes($query, '"') . "\" - use longer pattern " .
		 "or reduce error tolerance.";
		 
		 return $result;
	}
	$result->matches = $search->search($text);
	
	// number of matches
	$result->total = 0;
	foreach ($result->matches as $match)
	{
		$result->total++;
	}
	
	// get list of hits in the inout text (text selectors)
	$result->selector = array();

	if ($output_html)
	{
		// tags for HTML
		$tag = 'mark';
		$tag_starts = array();
		$tag_ends 	= array();
	}

	// If we have matches we want to get start and end positions of those matches in
	// the larger text so that we can display this (e.g., debugging)
	foreach ($result->matches as $pos => $d)
	{
		$text1 = $query;	
		$query_length = mb_strlen($query);
	
		// grab substring from target text, make it long enough to include mismatches	
		// by expanding either side by MAX_ERR
		$from 	= max(0, $pos - $query_length - $max_error);
		$to 	= min(mb_strlen($text), $pos + $max_error);	
		$text2 	= mb_substr($text, $from , $to - $from + 1);
		
		// get position of match in substring
		$alignment = compare($text1, $text2);
		
		//echo $alignment->alignment;
	
		// store this hit
		$hit = new stdclass;
		
		// store query string
		$hit->body = $original_query;
		
		// store the score of the alignment
		$hit->score = $query_length - $alignment->d;
		
		//$hit->alignment = $alignment;
		
		// location in haystack (i.e., text before any change in case)
		$start = $from + $alignment->range[0];
		$end = $from + $alignment->range[1];
		
		$hit->range = array($start, $end);
		
		// match in haystack
		$hit->exact = mb_substr($haystack, $start, $end - $start);
		
		$pre_length = min($start, FLANKING_LENGTH);
		$pre_start = $start - $pre_length;
	
		$hit->prefix = mb_substr($haystack, $pre_start, $pre_length, mb_detect_encoding($haystack)); 
		$post_length = min(mb_strlen($haystack, mb_detect_encoding($haystack)) - $end, FLANKING_LENGTH);		
		$hit->suffix = mb_substr($haystack, $end, $post_length, mb_detect_encoding($haystack)); 
		
		$result->selector[] = $hit;
		
		if ($output_html)
		{
			// tag the match
						
			if (!isset($tag_starts[$start]))
			{
				$tag_starts[$start] = array();
			}
			$tag_starts[$start][] = $tag;

			if (!isset($tag_ends[$end]))
			{
				$tag_ends[$end] = array();
			}
			$tag_ends[$end][] = $tag;
		}
	}
	
	if ($output_html)
	{
		// HTML for debugging
		
		// slit origjnal text into array of characters
		$text_array = mb_str_split($haystack);

		$result->html = '<pre>';

		foreach ($text_array as $pos => $char)
		{

			if (isset($tag_ends[$pos]))
			{
				foreach ($tag_ends[$pos] as $tag)
				{
					$result->html .= '</' . $tag . '>';
				}
			}


			if (isset($tag_starts[$pos]))
			{		
				foreach ($tag_starts[$pos] as $tag)
				{
					$result->html .=  '<' . $tag . '>';
				}
			}

			$result->html .=  $char;

		}

		$result->html .= '</pre>';
	}


	return $result;
}


// tests

if (0)
{

$text = '6 Proceedings of the Biological Society of Washington. 

NEMATOMA, new genus. 

Shells of ovate or elongate-ovate outline, covered with a thin perio- 
stracum. Nuclear whorls, judging from a fragment present, smooth, well 
roimded, with the last part of the last turn showing a feeble beginning of the 
postnuclear sculpture. Postnuclear whorls inflated, strongly rounded with 
an obsolete angle below the summit, which frequently gives the part 
between tliis and the summit a shouldered effect. The postnuclear whorls 
are marked by axial ribs, which evanesce on the base, and numerous spiral 
threads which are present on both spire and base. Aperture pear-shaped, 
decidedly channeled anteriorly with a feeble sinus in the outer lip near its 
summit. 

Type: Nematoma hokkaidoensis, new species (fig. 1). 

CURTITOMA, new genus. 

Shell short, stubby, ovoid. Postnuclear whorls strongly tabulatedly 
shouldered, but Avithout a keel at the angulation of the shoulder. Axial 
ribs very strong between the shoulder and periphery, evanescing on the 
base. The spiral sculpture consists of incised lines on the spire and threads 
on the columella. Aperture pear-shaped, decidedly channeled anteriorly, 
with a feeble sinus at the shoulder. 

Type: Curtitoma hecuba, new species (fig.. 3). 

VENUSTOMA, new genus. 

Shell small, varying in shape from ovate to broadly ovate. The first 
nuclear turn is smooth, the next shows the beginning of the spiral cords of 
the postnuclear sculpture. Postnuclear whorls with a roundly sloping 
shoulder, which extends over almost half of the turns and terminates in a 
well-marked angulation. The whorls are ornamented by well developed, 
sigmoid axial ribs and almost equally strong spiral cords, the junction of 
which produce rounded tubercles. The spiral sculpture of the shoulder is 
usually weaker and more crowded than that on the rest of the whorls. 
Suture moderately constricted. Periphery well rounded. Base moderately 
long, ornamented Hke the spire, but with the sculpture a little finer. 
Columella stout, marked by spiral cords and slender axial threads. Aperture 
pear-shaped, strongly channeled anteriorly, with a weak sinus at the shoulder 
on the outer Up. 

Type: Venustoma harucoa, new species (fig. 7). 

CANETOMA, new genus. 

Shell smaU, ovate. Nuclear whorls unknown; postnuclear whorls with a 
strong shoulder which extends over the posterior half of the turns; the 
anterior termination of the shoulder is marked by a spiral cord which 
renders the shell decidedly angulated here. The whorls are marked by 
well developed axial ribs and strong spiral cords that form rounded tubercles 
at their junction. In addition to this, there are finer spiral lirations and 
incremental fines that give the spaces between the axial ribs and spiral 
cords a fine reticulation, the whole producing a basket-Uke effect. Suture';


	$query = 'Venustoma harucoa';
	//$query = 'axial threads';
	
	$result = find_in_text($query, $text, true);
	
	print_r($result);


}

if (0)
{
	$json = '{
  "needle": "Angianthus micropoides",
  "haystack": "168 \nDistribution (Fig. 2): \nNullarbor Plain region. Common, \nEcology: \nOccurs on both clay and loam soils. Collectors’ notes include “Common on clayey \nsoils”, “Fine sandy loam over calcrete” and “In loam over limestone”. \nNote: \nl.A. conocephalus was originally described by Black (1929) as a variety of \nA. brachypappus. The var. conocephalus was considered to have a conical compound \nhead and var. brachypappus a cylindrical head. However the shape of the compound \nhead is quite variable. On the other hand both species exhibit distinct differences in habit \nand leaf morphology and usually pappus morphology. They are also allopatric. \nSelected Specimens Examined (5/23): \nWestern Australia — ApHn 1656, Forrest, 31.viii.1962 (PERTH); Chinnock 1151, 30 km S. of \nRawlinna, 19.ix.l973 (AD); George 8495, 30 miles NW. of Reid, 14.x. 1966 (PERTH). \nSouth Australia — Chinnock 1183, 15 km E. of Koonalda homestead, 21.ix.l973 (AD); Ising 1529, \nHughes, 8.ix.l920 (AD). \n8. Angianthus micropodioides (Benth.) Benth., FI. Austr. 3:565 (1867) {^micropo ides\'); \nGrieve & Blackall, W. Aust. Wildfls 812 (1975) {\'micropoides\'). — Phyllocalymma \nmicropodioides Benth., Enum. PI. Hueg. 62 (1837); Steetz in Lehm. PI. Preiss. 1:436 \n(1845). — Styloncerus micropodioides (Benth.) Kuntze, Rev. Generum PI. 367 (1891) \n{\'micropodes\'). Type: “Swan River. (Hiigel.).” Lectotype (here designated): Hugel s.n., \nSwan River, s. dat. (W). Isolectotype: K (see note 1 below). \nPhyllocalymma filaginoides Steetz in Lehm. PI. Preiss. 1:437 (1845); Steetz in \nWalper’s Repert. Bot. Syst. 6:229(1846). — Angianthus micropodioides filaginoides \nEwart & J. White, Proc. Roy. Soc. Viet. 22:92 (1909) {\'micropoides\'). Type: “In solo \narenoso — turfoso inter frutices ad fluvii Cygnorum ripam prope oppidulum Perth, \nmense Januario 1839. Herb. Preiss. No. 37.” Lectotype (here designated): Preiss 37, In \nNova Hollandia, (Swan-River Colonia) in solo arenoso turfoso inter frutices ad flumis \nCygnorum ripam leg. cl. Preiss, s. dat. (MEL 541603). Isolectotypes: LD, MEL 541604, \nMEL 541605 (ex herb. O. W. Sonder), MEL 583143 (ex herb O. W. Sonder), S, GH (ex \nherb. Klatt), (see p.l52). \nAnnual herb. Major axes ascending to erect, 4-15 cm long, hairy; stem sometimes \nsimple to c. 10 cm high, but usually forming major branches at basal and/or upper \nnodes. Leaves alternate, ± linear or lanceolate, 0.5-1. 5(2.8) cm long, 0.05-0.1 cm wide, \ndistinctly mucronate, variably hairy. Compound heads ± depressed ovoid to broadly \ndepressed ovoid, 0.4-0.6 cm long, 0.4-0.5 cm diam., axillary or terminal; bracts \nsubtending compound heads forming a conspicuous involucre exceeding the length of the \nhead, ofc. 10 leaf-like bracts, ± lanceolate to ± ovoid, 0.5-1. 5 cmlong, c. 0.1 cm wide, \nmucronate, hairy; general receptacle a small convex axis. Capitula c. 10-30 per \ncompound head; capitulum-subtending bracts 1, ± oblong or ovate, 2. 1-2.8 mm long, \n0.8-1. 3(1. 5) mm wide, the midrib variably hairy toward the apex. Capitular bracts with \nthe two concave ones 2. 4-3.1 mm long, the midrib hairy; flat bracts 2, obovate, ± \nabruptly attenuated in the lower Vi, 2. 4-3.1 mm long, (0.75)0.9-1.25 mm wide, the \nmidrib usually variably hairy toward the apex, rarely glabrous. Florets 2; corolla 5-lobed, \nthe tube tapering gradually towards the base in immature florets, a more abrupt taper in \nthe lower V 3 of mature florets which have variably swollen bases, 1.4-1. 9 mm long, \nc. 0.5 mm diam. Achenes ± obovoid, 0.8-1 mm long, 0. 5-0.6 mm diam., pubescent. \nPappus of 5 or 6 jagged scales fused at the base, each sc^e terminating in a single smooth \nor minutely barbellate bristle, the total pappus c. ‘73-^3 the length of the corolla \ntube. Fig. 3k. \nDistribution (Fig. 2): \nWestern Australia, particularly in the South West Drainage Division (Mulcahy & \nBettenay, 1972), between latitudes c.28°30\'S and 32°S and west of longitude c.l22°E. \nLocally common."
}';

	$doc = json_decode($json);
	
	
	if (json_last_error() != JSON_ERROR_NONE)
	{
		echo json_last_error_msg() . "\n";
	}
	
	//print_r($doc);
	

	$result = find_in_text($doc->needle, $doc->haystack, false, 2);
	
	print_r($result);


}

if (0)
{
	$needle = "Koonalda";
	$haystack = "\nl.A. conocephalus was originally described by Black (1929) as a variety of \nA. brachypappus. The var. conocephalus was considered to have a conical compound \nhead and var. brachypappus a cylindrical head. However the shape of the compound \nhead is quite variable. On the other hand both species exhibit distinct differences in habit \nand leaf morphology and usually pappus morphology. They are also allopatric. \nSelected Specimens Examined (5/23): \nWestern Australia — ApHn 1656, Forrest, 31.viii.1962 (PERTH); Chinnock 1151, 30 km S. of \nRawlinna, 19.ix.l973 (AD); George 8495, 30 miles NW. of Reid, 14.x. 1966 (PERTH). \nSouth Australia — Chinnock 1183, 15 km E. of Koonalda homestead, 21.ix.l973 (AD)";
	
$haystack = " “In loam over limestone”. \nNote: \nl.A. conocephalus was originally described by Black (1929) as a variety of \nA. brachypappus. The var. conocephalus was considered to have a conical compound \nhead and var. brachypappus a cylindrical head. However the shape of the compound \nhead is quite variable. On the other hand both species exhibit distinct differences in habit \nand leaf morphology and usually pappus morphology. They are also allopatric. \nSelected Specimens Examined (5/23): \nWestern Australia — ApHn 1656, Forrest, 31.viii.1962 (PERTH); Chinnock 1151, 30 km S. of \nRawlinna, 19.ix.l973 (AD); George 8495, 30 miles NW. of Reid, 14.x. 1966 (PERTH). \nSouth Australia — Chinnock 1183, 15 km E. of Koonalda homestead, 21.ix.l973 (AD); ";	


$needle = "Abascantus";
$haystack = " phaerico , convexe , intus bisinuato , concavo , apice sub torto. 

Abascantus sannio Schauf. — Convexus, obovatus, nitidus, 
 disperse-pilosus , cribrato-punctatus , abdomine supra glabro , pilosulo; 
 antennarum articulo primo elongato , ultimum articulum fere longitu- 
";

	echo mb_strlen($haystack) . "\n";
	
	$result = find_in_text($needle, $haystack, true, 2);
	print_r($result);


}

?>
