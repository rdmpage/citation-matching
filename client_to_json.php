<?php

// Client to test workflows 

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/taxon_name_parser.php');

//----------------------------------------------------------------------------------------
function match_name_and_citation($name_id, $name, $citation)
{
	
	// 0. Parse the taxon name to strip out authors	
	$pp = new Parser();
	$parser_result = $pp->parse($name);
	
	if (isset($parser_result->scientificName))
	{
		if ($parser_result->scientificName->parsed)
		{
			$name = $parser_result->scientificName->canonical;
		}
	}
		
	$output = new stdclass;
	
	$output->id				= $name_id;
	$output->scientificname = $name;
	$output->citation 		= $citation;
	
	
	// 1. Parse micro citation	
	$url = 'http://localhost/citation-matching/api_parser.php?';
	
	$parameters = array(
		'q' => $citation
	);
	
	$json = get($url . http_build_query($parameters));
	
	//echo $json . "\n";
	
	$doc = json_decode($json);
	
	if ($doc->status != 200)
	{
		$output->parsed = false;
	}
	else
	{		
		$output->parsed = true;
		
		// 2. Now find corresponding BHL page(s) and get page text		
		$url = 'http://localhost/citation-matching/api_bhl_ts.php';
		
		$json = post($url, $doc);
		
		//echo $json . "\n";

		$doc = json_decode($json);
		
		if ($doc && $doc->status == 200)
		{
			// 	BHL page?
			if (isset($doc->BHLPAGEID))
			{
				$output->bhl = join(".", $doc->BHLPAGEID);
			}
		
			// BHL part?(s)
			if (isset($doc->BHLPART))
			{
				$output->parts = array();
				foreach ($doc->BHLPART as $part)
				{
					$work = new stdclass;
					$work->title = $part->title;
					
					if (isset($part->DOI))
					{
						$work->doi = $part->DOI;
					}
					
					$output->parts[] = $work;
				}
			}

			// 3. If we have text for page, look for taxon name
			if (isset($doc->text))
			{
			
				foreach ($doc->text as $pageid => $text)
				{
					$query_doc = new stdclass;
					$query_doc->ignorecase 	= true;
					$query_doc->needle 		= $name;
					$query_doc->haystack 	= $text;
					
					// may need to be clever here, shorter strings should have smaller error
					//$query_doc->maxerror 	= 4; // 2 is default
					
					$url = 'http://localhost/citation-matching/api_text.php';		
					$json = post($url, $query_doc);
					
					$result = json_decode($json);
					
					if ($result->status == 200)
					{
						if ($result->total != 0)
						{
							// we have a match
							$output->matched = $pageid;
							
							$output->selector = $result->selector;
							$output->html 	  = $result->html;	
							
							if (isset($doc->image->{$pageid}))
							{
								$output->image 	  = $doc->image->{$pageid};
							}
						}
					}
				}			
			}
		}
	}
	
	return $output;
}


//----------------------------------------------------------------------------------------

$input_filename = 'test.tsv';
$input_filename = '53882.tsv';
$input_filename = 't.tsv';
$input_filename = 'bombay.tsv';
$input_filename = 'sheets/muelleria.tsv';
//$input_filename = 'Telopea.tsv';

//$input_filename = 'test.tsv'; // non BHL


$headings = array();
$row_count = 0;
$file = @fopen($input_filename, "r") or die("couldn't open $input_filename");
		
$file_handle = fopen($input_filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		"\t" 
		);		
	$go = is_array($row);
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			// get input data
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
		
			//print_r($obj);	
			
			// process
			if (isset($obj->scientificname) && isset($obj->citation))
			{			
				$output = match_name_and_citation($obj->id, $obj->scientificname, $obj->citation);
				//print_r ($output);
				
				echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

								
			}

		}
	}	
	$row_count++;
	
	if ($row_count > 5)
	{
		//break;
	}
}

?>
