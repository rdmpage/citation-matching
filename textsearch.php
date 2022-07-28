<?php

require_once (dirname(__FILE__) . '/config.inc.php');

require_once (dirname(__FILE__) . '/php-approximate-search/approximate-search.php');

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
	
	// alignment (this is done in reverse order)
	// we use the alignment to get start and end positions of the match in haystack
		
	$i = $m;
	$j = $n;
	
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
	//$output_html = false;
	
	$result = new stdclass;

	define ('MAX_ERR', $max_error);
	
	$query = $needle;
	$text = $haystack;
	
	if ($case_insensitive)
	{
		$text = mb_strtolower($text);
		$query = mb_strtolower($query);
	}

	// Search using approximate search
	$search = new Approximate_Search($query, MAX_ERR);
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
		$from 	= max(0, $pos - $query_length - MAX_ERR);
		$to 	= min(mb_strlen($text), $pos + MAX_ERR);	
		$text2 	= mb_substr($text, $from , $to - $from + 1);
		
		// get position of match in substring
		$alignment = compare($text1, $text2);
	
		// store this hit
		$hit = new stdclass;
		
		//$hit->alignment = $alignment;
		
		// location in haystack (i.e., text before any change in case)
		$start = $from + $alignment->range[0];
		$end = $from + $alignment->range[1];
		
		$hit->range = array($start, $end);
		
		// match in haystack
		$hit->text = mb_substr($haystack, $start, $end - $start);
		
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
  "needle": "Ptochoryctis rosaria",
  "haystack": "740 JOURNAL, BOMBAY NATURAL HISTORY SOCIETY , Vol.XVU, \n\nup in disc and not reaching dorstim ; beyond this a narrower fascia of purplish- \n crimson suffusion, edges irregularly dentate and marked with dark fuscous, \n obsolete towards dorsum, an acute median projection of posterior edge followed \n by some whitish suffusion ; a pale crimson streak mixed with white round apex \n and tornus : cilia deep yellow, obscurely barred with yellow-whitish, above \n apex suffused with orange on basal half. Hindwings pale grey, darker towards \n tornus ; cilia whitish-yellowish. \n\nKhasi Hills ; two specimens. \n\nParadoris, n. n. \n\nI propose this name for the genus described by Heinemann under the name \n of Euteles, which is preoccupied in Coleoptera (18341 \n\nParadoris anaphracta, n. sp. \n\ng. 12-13 mm. Head ochreous-whitish, slightly sprinkled with fuscous. Palpi \n fuscous irrorated with blackish, apex of second joint white, terminal joint \n whitish with a few dark fuscous specks. Antennas fuscous. Thorax ochreous- \n whitish sprinkled with fuscous, shoulders narrowly dark fuscous. Abdomen \n pale ochreous, more or less suffused with fuscous. Forewings elongate, rather \n narrow, costa gently arched, apex obtuse, termen very obliquely rounded ; \n ochreous-whitish or pale whitish-ochreous, thinly sprinkled with fuscous ; a \n small blackish spot on base of costa ; stigmata blackish, plical somewhat beyond \n first discal, second discal connected by a slightly incurved blackish streak with \n dorsum before tornus, followed by an undefined band of darker irroration from \n | of costa to tornus ; undefined spots of blackish irroration round apex and \n termen : cilia ochreous-whitish. Hindwings grey ; cilia pale whitish-ochreous. \n\nBhotan (4,500 feet), from May to July ; three specimens (Dudgeon). \n\nPtochoryctis rosaria, n. sp. \n\n#$. 23-24 mm. Head, thorax, and abdomen snow-white. Palpi and an- \n tennae grey. Forewings elongate, moderately broad, costa moderately arched, \n apex rounded-obtuse, termen obliquely rounded, shining snow-white ; a sub- \n marginal series of seven black dots round apex and termen : cilia white. Hind- \n\nings and cilia white. \n\n•-•hotau, in April and May ; two specimens (Dudgeon). \n\nTrypherantis , n. g. \n\nHead with appressed scales, side-tufts loosely raised ; tongue obsolete. An- \n tennae |, in $ moderately ciliated, basal joint moderate, without pecten. Labial \n palpi long, recurved, with appressed scales, terminal joint somewhat shorter \n than second, acute. Maxillary palpi rudimentary. Posterior tibia? clothed with \n rough hairs above. Forewings with 16 furcate, 2 from |, 4 and 5 approximated, \n 7 and 8 stalked, 7 to termen, 9 absent, 11 from about middle. Hindwings over \n 1, ovate, cilia \\ ; 3 and 4 stalked, 5 parallel, 6 and 7 short stalked. \n\nApparently allied to Ptochoryctis. \n\nTrypherantis atelogramrna, n. sp. \n\n$ . 24-2G mm. Head, palpi, and antennae ochreous-whitish, base of palpi \n fuscous. Thorax and abdomen whitish-ochreous. Forewings elongate, pos- \n"
}';

	$doc = json_decode($json);
	
	if (json_last_error() != JSON_ERROR_NONE)
	{
		echo json_last_error_msg() . "\n";
	}
	
	print_r($doc);
	
	echo 'x';

	//$result = find_in_text($doc->needle, $doc->haystack, true);
	
	//print_r($result);


}

?>
