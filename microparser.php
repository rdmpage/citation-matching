<?php

// Parse a microcitation

//----------------------------------------------------------------------------------------
// Output the regex matching using XML-style tags for debugging, and with an eye on
// generating training data.
function match_to_tags($text, $matches, $debug = false)
{
	$terminating_character = '•';

	$xml = '';
	
	$xml .= '<sequence>';
	
	$length = mb_strlen($text);
	
	if ($debug)
	{
		// visual debugging
		$ticks = array();
	
		for ($i = 0; $i < $length; $i++)
		{
			$ticks[] = ' ';
		}
	
		foreach ($matches as $k => $match)
		{
			if (is_numeric($k))
			{
				// skip
			}
			else
			{
				$start = $match[1];	
						
				// handle problem that PREG_OFFSET_CAPTURE doesn't work with UTF-8
				$start = mb_strlen(substr($text, 0, $start), 'UTF-8');			
				$ticks[$start] = '|';						
				$match_length = mb_strlen($match[0]);			
				$ticks[$match[1] + $match_length ] = '|';			
			}
		}
		echo join('', $ticks) . "\n";
		echo $text . "\n";
	}
	
	// split text into character array
	$text_array = mb_str_split($text);

	// append a terminating character so last tag can be added
	$text_array[] = $terminating_character;
			
	$tag_starts = array();
	$tag_ends 	= array();
	
	// process labelled matches in regex
	foreach ($matches as $tag => $match)
	{
		if (is_numeric($tag))
		{
			// skip
		}
		else
		{
			switch ($tag)
			{
				// any tags we want to "eat"
				case 'page':
					break;

				// any remaining tags
				default:
					$start = $match[1];	
			
					// handle problem that PREG_OFFSET_CAPTURE doesn't work with UTF-8
					$start = mb_strlen(substr($text, 0, $start), 'UTF-8');
					
					if (!isset($tag_starts[$start]))
					{
						$tag_starts[$start] = array();
					}
					$tag_starts[$start][] = $tag;
			
					$match_length = mb_strlen($match[0]);
					$end = $start + $match_length;
						
					if (!isset($tag_ends[$end]))
					{
						$tag_ends[$end] = array();
					}
					$tag_ends[$end][] = $tag;	
					break;
			}		
		}
	}
	
	//print_r($tag_starts);
	//print_r($tag_ends);
	
	foreach ($text_array as $pos => $char)
	{		
		// close any tags that end here
		if (isset($tag_ends[$pos]))
		{
			foreach ($tag_ends[$pos] as $tag)
			{
				$xml .= '</' . $tag . '>';
			}
		}
	
		// open any tags that start here
		if (isset($tag_starts[$pos]))
		{		
			foreach ($tag_starts[$pos] as $tag)
			{
				$xml .= '<' . $tag . '>';
			}
		}
		
		if ($char != $terminating_character)
		{
			$xml .= $char;	
		}
	}

	$xml .= '</sequence>';
	return $xml;
}

//----------------------------------------------------------------------------------------
// Measure how much of input string is "covered" by the regular expression
// need to handle cases where tags may be nested
function match_span($text, $matches, $debug = false)
{
	$length = mb_strlen($text);

	$coverage = array_fill(0, $length, 0);

	// process labelled matches in regex
	foreach ($matches as $tag => $match)
	{
		if (is_numeric($tag))
		{
			// skip
		}
		else
		{
			// fill any positions in the range of this tag with "1"
			
			$start = $match[1];	
	
			// handle problem that PREG_OFFSET_CAPTURE doesn't work with UTF-8
			$start = mb_strlen(substr($text, 0, $start), 'UTF-8');
			
			$match_length = mb_strlen($match[0]);
			$end = $start + $match_length;
			
			for ($i = $start; $i < $end; $i++)
			{
				$coverage[$i] = 1;
			}
		}
	}

	//echo $text . "\n";
	//echo join('', $coverage) . "\n";
	
	$count = 0;
	foreach ($coverage as $c)
	{
		if ($c == 1)
		{
			$count++;
		}
	}
	
	// percent coverage
	$score = 100 * $count / $length;
	
	return $score;
}

//----------------------------------------------------------------------------------------
// Parse citation using regex.
// likley to eventually need multiple patterns here...
function parse($text)
{
	$obj = new stdclass;
	$obj->status = 404;
	$obj->citation = $text;
	$obj->score = 0;

	// regex expressions to match microcitations
	// note that we include anything that looks ike a delimiter as these could be 
	// useful if we ever move to an appraoch such as CRF
	
	$comment_pattern = '(\s*\[[^\]]+\])?';

	$date_pattern 		= "(\d+\s+)?([A-Z]|[a-z]|\'|-)+(\s+\d+)?";

	// include delimiter after journal, also include series in this pattern
	$journal_pattern 	= "(?<journal>.*)(\s+\(\d+\))?[,]?";
	
	$journal_pattern = "(?<journal>([\p{L}]+[\.|,]?)((\s+[\p{L}]+[\.|,]?)+)?[,]?(\s+\(\d+\))?)";
	$journal_pattern = "(?<journal>([\p{L}]+[\.|,]?)((\s+[\p{L}]+[\.|,]?)+)?[,]?(\s+\((N.S.|\d+)\))?)";
	$journal_pattern = "(?<journal>([\p{L}]+[\.|,]?)((\s+[\p{L}]+[\.|,]?)+)?[,]?(\s+\((N.S.|\d+)\))?(\([A-Z]\))?)";
	$journal_pattern = "(?<journal>([\p{L}]+\.?,?)((\s*[\p{L}]+\.?\,?)+)?[,]?(\s+\((N.S.|\d+)\))?(\([A-Z]\))?)";
	$journal_pattern = "(?<journal>([\p{L}]+\.?,?)((\s*[\p{L}]+\.?\,?)+)?[,]?(\s+\([^\)]+\))?)";

	// include issue in "volume", and include delimiter
	$volume_pattern 	= "(?<volume>\d+[A-Z]?(-\d+)?(\s*\(\d+(-\d+)?\))?[,|:]?)";
	$volume_pattern 	= "(?<volume>\d+[A-Z]?(-\d+)?(\s*\(\d+(-\d+)?\))?(,?\s+fasc\.\s+\d+)?[,|:]?)";
	$volume_pattern 	= "(?<volume>\d+[A-Z]?(-\d+)?(\s*\(\d+(-\d+)?\))?(,?\s+(no|fasc)\.\s+\d+)?[,|:]?)";

	$volume_pattern 	= "(?<volume>(No.\s+)?\d+[A-Z]?(-\d+)?(\s*\(\d+(-\d+)?\))?(,?\s+(no|fasc)\.\s+\d+)?[,|:]?)";
	$volume_pattern 	= "\s*(?<volume>(No.\s+)?\d+[A-Z]?(-\d+)?(\s*\([^\)]+\))?(,?\s+(no|fasc)\.\s+\d+)?[,|:])";


	// include delimiter
	$page_pattern 		= "(?<page>(\d+|[xvlci]+))" . $comment_pattern . "[\.]?";

	// figures, plates, etc.
	$extra_pattern = "(,\s+(.*))?";
	
	$collation_pattern = '\s*(?<collation>' . $page_pattern  . $extra_pattern . ')';
	
	
	$journal_simple = '(?<journal>(\s*[\-|\']?[\p{L}]+[\.]?[,]?)+(\s*\([^\)]+\))?)';

	// Biologia cent.-am. (Zool.) Lepid.-Heterocera
	$journal_para = '(?<journal>(\s*[\-]?[\p{L}]+[\.]?[,]?)+(\s*\([^\)]+\))\s*(\s*[\-]?[\p{L}]+[\.]?[,]?)+)';
		
		
	// authorship
	
	$name_pattern = '((\p{Lu}\.)+\s*)?\p{Lu}\p{L}+';
	
	// in(,?\s+(\p{Lu}\p{L}+))+\s+&\s+(\p{Lu}\p{L}+|al\.),\s+
	
	$in_authors = '(,?\s*' . $name_pattern . ')+(\s+&\s+' . '(' . $name_pattern . '|al\.))?';
		

	$matched = false;	
	$matches = array();
	
	$patterns = array(
	
		// authorship prefix
		'/^(?<author>[I|i]n\s+' . $in_authors . ',)\s+' . $journal_simple . $volume_pattern . $collation_pattern . '/u',
		'/^(?<author>[I|i]n\s+' . $in_authors . ',)\s+' . $journal_simple . '[,|:]' . $collation_pattern . '/u',
		
		// complex journal pattern
		'/^' . $journal_para  .  $volume_pattern . $collation_pattern . '/u',
		
		// articles
		'/^' . $journal_simple . $volume_pattern . $collation_pattern . '/u',
		'/^' . $journal_simple . $volume_pattern . '/u',

		// monographs
		'/^' . $journal_simple . '[,|:]' . $collation_pattern . '/u',
		
		// simple pattern
		'/^' . $journal_simple . '/u'
	);
	
	//print_r($patterns);
	
	//exit();
	
	$num_patterns = count($patterns);
	
	
	$i = 0;

	while ($i < $num_patterns && !$matched)
	{
		if (preg_match($patterns[$i], $text, $matches, PREG_OFFSET_CAPTURE))
		{
			$obj->pattern = $patterns[$i];
			$matched = true;
		}
		else
		{
			$i++;
		}
	}
	
	
	/*

	if (!$matched)
	{

		$pattern = '/'
		    . '^'
     		. $journal_pattern 
			. '\s*' 
			. $volume_pattern 
			. '\s*' 
			. '(?<collation>' // "collation pattern includes pages, plates, and figures"
			. $page_pattern 
			. $extra_pattern
			. ')'
			. '$'
			. '/xu';
			
		echo $pattern;
	
		if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) 
		{
			//print_r($matches);
		
			$matched = true;
		}
	}
	*/
		
	if ($matched)
	{		
		// populate object from matching	
		// try to mimic a complete CSL-JSON record as much as possible
		
		$obj->status = 200;	
		
		foreach ($matches as $tag => $match)
		{
			if (is_numeric($tag))
			{
				// skip
			}
			else
			{
				if ($match[0] != '')
				{
					switch ($tag)
					{
						case 'author':
							$authorstring = preg_replace('/^[I|i]n\s+/', '', $match[0]);
							$authorstring = preg_replace('/,$/', '', $authorstring);
					
							$names = preg_split('/(,\s+|\s+&\s+)/', $authorstring);
					
							foreach ($names as $name)
							{
								$author = new stdclass;
								
								if (preg_match('/[\.|,]/', $name))
								{
									$author->literal = $name;
								}
								else
								{
									$author->family = $name;
								
								}
								$obj->author[] = $author;
							}
							break;
					
					
						case 'journal':
							$obj->{'container-title'} = $match[0];
							break;
						
						default:
							$obj->{$tag} = $match[0];
							break;				
					}
				}				
			}
		}
		
		// clean object (e.g., remove delimiters)		
		foreach ($obj as $k => $v)
		{
			switch ($k)
			{
			
				case 'container-title':
					$obj->{$k} = preg_replace('/\,$/', '', $obj->{$k});
					$obj->{$k} = preg_replace('/No\.$/', '', $obj->{$k});
					break;
					
				case 'volume':
					$obj->{$k} = preg_replace('/[,|:]$/', '', $obj->{$k});
					
					$clean_matched = false;
					
					if (!$clean_matched)
					{
						if (preg_match('/(?<volume>\d+)\s*\((?<issue>[^\)]+)\)/', $obj->{$k}, $m))
						{
							$obj->volume = $m['volume'];
							$obj->issue = $m['issue'];
							
							$clean_matched = true;
						}					
					}
					
					if (!$clean_matched)
					{
						if (preg_match('/(?<volume>\d+),?\s*no\.?\s*(?<issue>\d+)/i', $obj->{$k}, $m))
						{
							$obj->volume = $m['volume'];
							$obj->issue = $m['issue'];
							
							$clean_matched = true;
						}					
					}
					
					break;
					
				case 'page':
					//$obj->{$k} = preg_replace('/\.$/', '', $obj->{$k});
					break;
			
				default:
					break;
			}
		
		}
		
		// to do: parse the "colaltion" into pages, plates, figures, etc.
		
		// add tagging
		$obj->xml = match_to_tags($text, $matches);
		
		// score the match			
		$obj->score = round(match_span($text, $matches), 2);	
	}
	else
	{
		$obj->status = 404;
		$obj->message = "Unable to parse text";
		
	}
		
	return $obj;
}


//
// "tests"

if (0)
{
	$publications = array(
	/*
	'Trans. Linn. Soc. London, Zool., (2) 1, 585.',
	'J. Proc. Linn. Soc. London, Zool., 7 (1864), 61.',
	'Canad. Ent., 9, 70.',
	'Ann. Mag. nat. Hist., (8) 6, 587.',
	'Proc. malac. Soc. London 15, 20',
	'Vertebrata Palasiatica 32(3): 163.',
	'Publs Seto mar. biol. Lab. 7: 256.',
	*/
	//'Bull. Soc. zool. France, 54, 327.',
	//'Bull. Inst. océan. Monaco, No. 575, 13.',

	//'Recu. vétér., 86, fasc. 9, 337.',

	//'Science, (N.S.) 16, no. 402, 434.',
	//'Revta ibér. Parasit. 18: 315.',

	'Mém. Mus. natn. Hist. nat. Paris (N.S.)(A)37: 75, pl. 3, fig. 14.',
	'Mém. Mus. natn. Hist. nat. Paris (N.S.)(A)37: 75.',
	'Mém. Mus. natn. Hist. nat. Paris (N.S.) 37: 75.',

	'Allan. (1929). In: Genetica, 11: 505.',

	'Bull. N.Y. St. Mus. No. 265: 43.',

	'Vertebrata palasiat. 24 (1): 79.',
	'Mems Inst.Butantan 36: 209.',
	'Ann. Mag. nat. Hist., 2 (9), 188.',

	'Neues Jb. Geol. Paläont. Mh. 1959: 424.',

	'Ofvers. Finska Förh., 50, no. 7, 73.',
	);

	$publications = array(

	"S.B. Ges. Morph. Phys., Münich, 31, 37.",
	"Proc. U.S. nat. Mus., 58, 57, 74.",
	"Entomologist, 53, 126.",
	"Mem. Inst. Oswaldo Cruz, 12, 73.",
	"S.B. Ges. Morph. Phys. Münich, 31, 45.",
	"Contr. Sci. Nematology, 9, 321.",
	"Mem. Col., 9, 456.",
	"Ohio J. Sci., 21, 88.",
	"Bull. Soc. nat. Sci. Buffalo, 12, 151.",
	"Zool. Jahrb., Syst., 39, 652.",
	"Tijdschr. Nederl. dierl. Ver., (2) 12, 5.",
	"Insects Samoa 3(Lepid. 2): 80.",

	"Bull.zool.Mus.Univ.Amsterdam 4: 197.",
	"Bull.Mus.natn.Hist.nat.,Paris (Ser.2) 41: 1264.",
	"Bull.Sth.Calif.Acad.Sci. 76: 57.",


	);
	
	
	$publications = array(
	//'Ofvers. Finska Förh., 50, no. 7, 73.',
	//	'Mém. Mus. natn. Hist. nat. Paris (N.S.)(A)37: 75, pl. 3, fig. 14.',
	// 'Insecta kor. 12: 27, 28 [keys], 32, figs 26, 27, 103, pl. C, fig. 17.',
	
	'in Mey, Esperiana (Mem.)6: 202, pl. 1, fig. 6, pl. 6, fig. 32, pl. 32, fig. 4.',
	'Korean J. appl. Ent. 29: 142, figs 3, 11-13.',
	'Samml. eur. Schmett. 8: pl. 45, fig. 312.',
	'Samml. eur. Schmett. 8: 60, pl. 24, fig. 166.',
	
	'in Walsingham & Hampson, Proc. zool. Soc. Lond. 1896: 278.',
	'Mém. Mus. natn. Hist. nat. Paris (N.S.)(A)37: 75, pl. 3, fig. 14.',
	'Proc. zool. Soc. Lond. 1907: 944, pl. 51, fig. 16.',
	);
	
	$publications = array(
//	'Eur. J. Ent. 107: 249 [key], 264, figs 21, 45, 68, 86.',
	'Biol. issled. estest. kultur. ekosist. Primorsk. Kray: 208, figs 3, 16, 17.'
	);

	$publications = array(
	'Eur. J. Ent. 107: 249 [key], 264, figs 21, 45, 68, 86.',
	'Biol. issled. estest. kultur. ekosist. Primorsk. Kray: 208, figs 3, 16, 17.',
	"S.B. Ges. Morph. Phys. Münich, 31, 45.",
	'Mém. Mus. natn. Hist. nat. Paris (N.S.) 37: 75.',
	'Mém. Mus. natn. Hist. nat. Paris (N.S.)(A)37: 75, pl. 3, fig. 14.',
	'Science, (N.S.) 16, no. 402, 434.',
	"Insects Samoa 3(Lepid. 2): 80.",
	'in Walsingham & Hampson, Proc. zool. Soc. Lond. 1896: 278.',
	'Ann. Mag. nat. Hist., (8) 6, 587.',
	"Bull.Mus.natn.Hist.nat.,Paris (Ser.2) 41: 1264.",
	);
	
	
	$publications = array(
	'Science, (N.S.) 16, no. 402, 434.',
	'Ann. Mag. nat. Hist., (8) 6, 587.',
	"Bull.Mus.natn.Hist.nat.,Paris (Ser.2) 41: 1264.",
	'Biol. issled. estest. kultur. ekosist. Primorsk. Kray: 208, figs 3, 16, 17.',
	);
	
	
	$publications = array(
	'Pan-Pacific Ent. 3: 137.',
	'Verh. zool.-bot. Ges. Wien 18(Abh.): 614.',
	'Biologia cent.-am. (Zool.) Lepid.-Heterocera 4: 35.',
	
	'Entomologist\'s mon. Mag. 47: 13.',
	'Arch. Naturgesch. 85(A)(4): 63.',
	'Acta ent. bohemoslovaca 73: 175; 182 [key], figs 1, 2, 6, 8, 9.',
	'(May 9), Proc. U.S. natn. Mus. 25: 853 [key], 865.',
	'In Alluaud & Jeannel, Voyage Alluaud & Jeannel Afr. or. (Lépid.) 2: 71.',
	'Voyage Alluaud & Jeannel Afr. or. (Lépid.) 2: 71.',
	'In Caradja & Meyrick, Dt. ent. Z. Iris 52: 4.',
	'Entomologist\'s Gaz. 49: 40, figs 5, 6, 9, 10, 15, 18, 22-24.',
	'in Powell & Povolný, Holarctic Lepid. 8(Suppl. 1): 6, figs 13, 31.',
	);
	
	
	$publications = array(
	'In Wocke & Staudinger, Stettin. ent. Ztg 23: 236.',
	'In Joannis, Annls Soc. ent. Fr. 98(Suppl.): 724 [486].',
	'In Caradja & Meyrick, Mater. Microlepid. Fauna chin. Provinzen Kiangsu, Chekiang, Hunan: 75.',
	);
	

	foreach ($publications as $text)
	{
		$obj = parse($text);
		
		print_r($obj);
	}
}


?>
