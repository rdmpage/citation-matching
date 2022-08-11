<?php

// Client to test workflows 

require_once (dirname(__FILE__) . '/api_utilities.php');

//----------------------------------------------------------------------------------------
function match_name_and_citation($name_id, $name, $citation)
{
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
		
		// echo $json . "\n";

		$doc = json_decode($json);
		
		if ($doc->status == 200)
		{
			// 	BHL page?
			if (isset($doc->BHLPAGEID))
			{
				$output->bhl = join(".", $doc->BHLPAGEID);
			}
		
			// BHL part? just accept one for now
			if (isset($doc->BHLPART))
			{
				$output->title = $doc->BHLPART[0]->title;
				
				if (isset($doc->BHLPART[0]->DOI))
				{
					$output->doi = $doc->BHLPART[0]->DOI;
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
							
							// store one match (to do: think about if we have more)
							
							$selector = $result->selector[0];
							
							$output->prefix = $selector->prefix;
							$output->text 	= $selector->text;
							$output->suffix = $selector->suffix;
							
							$output->prefix = preg_replace("/\n/", '\n', $output->prefix);
							$output->suffix = preg_replace("/\n/", '\n', $output->suffix);
						}
					}
				}			
			}
		}
	}
	
	return $output;
}


//----------------------------------------------------------------------------------------

$output_keys = array('id', 'scientificname', 'citation', 
	'parsed', 'bhl', 'title', 'doi', 'matched',
	'prefix', 'text', 'suffix'	
	);
	
echo join("\t", $output_keys) . "\n";

$input_filename = 'test.tsv';
$input_filename = '53882.tsv';
$input_filename = 't.tsv';
$input_filename = 'bombay.tsv';


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
				
				$output_row = array();
				foreach ($output_keys as $key)
				{
					if (isset($output->{$key}))
					{
						$output_row[] = $output->{$key};
					}
					else
					{
						$output_row[] = "";
					}
				}
				
				echo join("\t", $output_row) . "\n";
				
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
