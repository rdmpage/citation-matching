<?php

// experiments parsing collation information

//----------------------------------------------------------------------------------------
function collation_parser($string, $debug = false)
{
	// definitions
	$range_split_pattern = '[-|–]';
	$termination_symbol  = '•';

	// clean
	$string = trim($string);
	
	$result = new stdclass;	
	$result->string = $string;
		
	// tokenise	by splitting into array of symbols
	// space delimits tokens
	// comma indicates an item in a list
	// [] enclose comments (as do ()?)
	// . is treated as part of token (except for last token)
	// 
	
	$tokens = array();
	
	$n 		= mb_strlen($string);	
	$i 		= 0;
	$token 	= '';	
	$ch 	= '';
	
	$state = 0;	
	while ($state != 100)
	{
		if ($debug)
		{
			echo "$state $ch $token\n";
		}
	
		switch ($state)
		{
			case 0:
				if ($i == $n)
				{
					$state = 99;
				}
				else
				{			
					$ch = mb_substr($string, $i, 1);
					$i++;				
				
					switch ($ch)
					{
						case ' ':
							$state = 1;
							break;
						
						case ',':
							$state = 2;
							break;
						
						case '[':
						case '(':
							$token = $ch;
							$state = 3;
							break;
												
						default:
							$token .= $ch;
							break;
				
					}
				}
				break;
				
			case 1:
				if ($token != '')
				{
					$tokens[] = $token;
					$token = '';
				}
				$state = 0;
				break;
		
			case 2:		
				if ($token != '')
				{
					$tokens[] = $token;
				}
				$token = $ch;
				$tokens[] = $token;
				$token = '';
				$state = 0;
				break;
				
			case 3:
				if ($i == $n)
				{
					$state = 99;
				}
				else
				{			
					$ch = mb_substr($string, $i, 1);
					if ($ch == ']' || $ch == ')')
					{
						$state = 0;
					}
					else
					{
						$token .= $ch;
						$i++;
					}					
				}
				break;
				
			case 99:
				if ($token != '')
				{
					$token = preg_replace('/\.$/', '', $token);
					$tokens[] = $token;					
				}
				$tokens[] = $termination_symbol;
				$state = 100;
				break;
				
			default:
				break;
				
		}
	
	}		
	$result->tokens = $tokens;
	
	//print_r($tokens);

	// now that we have a stream of tokens we convert them to something structured
	// we classify each token by type
	
	
	// structured representation of data
	$locator = new stdclass;	
	$locator->page = array();
	$locator->plate = array();
	$locator->figure = array();
	$locator->textfigure = array();
	
	// stack to hold current token(s)
	$stack = array();

	// store classification for each toekn for debugging
	$result->classification = array();
	
	// Initial state assumes that we are starting with page number(s)	
	// state changes depending on text labels such as "pl." and "fig."
	$state = 'page';
	
	foreach ($tokens as $token)
	{
		$c = new stdclass;
		$c->token = $token;
	
		// classify token
		$type = 'unknown';
		
		if ($type == 'unknown')
		{
			if (preg_match('/^[\[|\(].*[\]|\)]$/', $token))
			{
				$type = 'comment';
			}
		}
		
		// page prefix
		if ($type == 'unknown')
		{
			if (preg_match('/^p\.?$/', $token))
			{
				$type = 'category';
			}
		}
		

		// a number
		if ($type == 'unknown')
		{
			if (preg_match('/^[0-9]+$/', $token))
			{
				$type = 'label';
			}
		}
		
		// a numerical range
		if ($type == 'unknown')
		{
			if (preg_match('/^[0-9]+' . $range_split_pattern . '[0-9]+$/u', $token))
			{
				$type = 'range';
			}
		}		
		
		// a comma
		if ($type == 'unknown')
		{
			if (preg_match('/,$/', $token))
			{
				$type = 'add';
			}
		}		

		// figure label
		if ($type == 'unknown')
		{
			if (preg_match('/^figs?\.?$/i', $token))
			{
				$type = 'category';
				$state = 'figure';
			}
		}
		
		// text figure label
		if ($type == 'unknown')
		{
			if (preg_match('/^text-figs?\.?$/i', $token))
			{
				$type = 'category';
				$state = 'textfigure';
			}
		}		

		// plate label
		if ($type == 'unknown')
		{
			if (preg_match('/^(pls?\.?|plates?)$/i', $token))
			{
				$type = 'category';
				$state = 'plate';
			}
		}
		
		// 
		if ($type == 'unknown')
		{
			if ($token == $termination_symbol)
			{
				$type = 'end';
			}
			else
			{
				$type = 'label';
			}
		}
		
		if ($debug)
		{
			echo 
				str_pad($state, 10, ' ', STR_PAD_LEFT) 
				. '|'		
				. str_pad($type, 10, ' ', STR_PAD_LEFT) 
				. '|'	
				. "$token\n";
		}
		
		$c->type = $type;
		
		$result->classification[] = $c;
			
		switch ($type)
		{
			// how to handle potential child - parent relationships, e.g. figs as part of plates?
			case 'label':
				$thing = new stdclass;
				$thing->name = $token;
				$stack[] = $thing;
				break;
				
				// unpack numerical range
			case 'range':
				$is_numeric_range = false;
			
				$parts = preg_split('/' . $range_split_pattern . '/u', $token);
				
				if (count($parts) == 2)
				{
					if (is_numeric($parts[0]) && is_numeric($parts[1]))
					{
						// we have a numerical range
						$is_numeric_range = true;
						
						$from 	= (Integer)$parts[0];
						$to 	= (Integer)$parts[1];
						
						// ensure sensible order
						if ($to < $from)
						{
							$tmp = $to;
							$to = $from;
							$from = $tmp;
						}
						
						// add each member of range to the stack in reverse order
						// so they will be popped off correctly
						for ($i = $to; $i >= $from; $i--)
						{
							$thing = new stdclass;
							$thing->name = $i;
							$stack[] = $thing;											
						}
					}				
				}
				
				// if not simple numbers just add token "as is"
				if (!$is_numeric_range)
				{
					$thing = new stdclass;
					$thing->name = $token;
					$stack[] = $thing;				
				}				
				break;
				
				// assume comment applies to everything currently on the stack,
				// this means a comment after a range such as 10-12 will apply
				// to each item individually
			case 'comment':
				if (!empty($stack))
				{
					$n = count($stack);
					for ($i = 0; $i < $n; $i++)
					{
						$stack[$i]->comment = $token;
					}
				}
				break;
				
			case 'add':
			case 'end':
				while (!empty($stack))
				{
					$value = array_pop($stack);					
					$locator->{$state}[] = $value;
				}
				break;
		
			default:
				break;
		}
	
		
	
	}
	
	$result->locator = $locator; 
	
	return $result;
}


//----------------------------------------------------------------------------------------
// test
if (0)
{
	$strings = array(
	//"27, 28 [keys], 32, figs 26, 27, 103, pl. C, fig. 17.",
	//" 19, 28, 30 [keys] 83, pl. 2, figs 11, 12, pl. D, figs 3, 6, pl. V, fig. 2.",
	//" 47, figs 7, 10, 11.",
	//"321, 25, 30 [keys], 40, pl. 4, fig. 2, pl. A, figs 3, 4, pl. Q, fig. 1.",

	//" 191 [key], 202; 1853, pl. 63, fig. 459 [legend non-binominal].",

	//" 1 [abstracts, as pusilella],4 [key, as pusilella], 13, figs 1, 2, 37, 38.",


	//"  128, figs 17, 36, 53, 76, 77.",
	//" 365–367, plate 21.",


//		"1 [hi] 3-5,7 ",
//		"2-4 [keys], 19, text-fig. 9, pl. 2, fig. 12.",
		
		"p. 144",
	);

	foreach ($strings as $string)
	{
		$result = collation_parser($string, true);
		
		print_r($result);

	

	}
}

?>
