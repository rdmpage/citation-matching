<?php

// Parse a microcitation

//----------------------------------------------------------------------------------------
// Output the regex matching using XML-style tags for debugging, and with an eye on
// training data.
function match_to_tags($text, $matches, $debug = false)
{
	$terminating_character = '•';

	$xml = '';
	
	$xml .= '<sequence>';
	
	$length = mb_strlen($text);
	
	// visual debug
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
						
			// handle probelm that offsets don't work with UTF-8
			$start = mb_strlen(substr($text, 0, $start), 'UTF-8');
			
			$ticks[$start] = '|';
						
			$match_length = mb_strlen($match[0]);
			
			$ticks[$match[1] + $match_length ] = '|';			
		}
	}
	
	if ($debug)
	{
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
			$start = $match[1];	
			
			// handle problem that offsets don't work with UTF-8
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
// Parse citation using regex.
function parse($text)
{
	$obj = new stdclass;
	$obj->status = 404;
	$obj->text = $text;

	// regex expressions to match microcitations
	// note that we include anything that looks ike a delimiter as these could be 
	// useful if we ever move to an appraoch such as CRF

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
	$volume_pattern 	= "(?<volume>(No.\s+)?\d+[A-Z]?(-\d+)?(\s*\([^\)]+\))?(,?\s+(no|fasc)\.\s+\d+)?[,|:]?)";

	//$volume_pattern 	= "(?<volume>(\([^\)]+\)\s*)?\d+[A-Z]?(-\d+)?(\s*\(\d+(-\d+)?\))?[,|:]?)";

	// include delimiter
	$page_pattern 		= "(?<pages>(\d+|[xvlci]+)[\.]?)";

	// figures, plates, etc.
	$extra_pattern = "(?<extra>,\s+(.*))?";

	$matched = false;
	
	$matches = array();

	if (!$matched)
	{

		$pattern = '/'
     		. $journal_pattern 
			. '\s*' 
			. $volume_pattern 
			. '\s*' 
			. $page_pattern 
			. $extra_pattern
			. '$'
			. '/xu';
	
		if (preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE)) 
		{
			//print_r($matches);
		
			$matched = true;
		}
	}
		
	if ($matched)
	{		
		// populate object from matching	
		
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
					
				case 'pages':
					$obj->{$k} = preg_replace('/\.$/', '', $obj->{$k});
					break;
			
				default:
					break;
			}
		
		}
		
		// add tagging
		$obj->xml = match_to_tags($text, $matches);	
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
	'Ofvers. Finska Förh., 50, no. 7, 73.',
	);


	foreach ($publications as $text)
	{
		$obj = parse($text);
		
		print_r($obj);
	}
}


?>
