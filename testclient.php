<?php

// Client to test workflows 

require_once (dirname(__FILE__) . '/api_utilities.php');


$citations = array(
	'Paradoris anaphracta' => 'J. Bombay nat. Hist. Soc. 17: 740.', 
	
	//'Venustoma harucoa' => 'Proc. biol. Soc. Wash., 54, 6.',
	
	//'Anacampsis simplicella' => 'Proc. Linn. Soc. N.S.W. 29: 305.',
	//'Anacampsis nerteria' => 'J. Bombay nat. Hist. Soc. 17: 139.',
	
	//'Telphusa destillans' => 'Exotic Microlep. 2 (5): 133.',
	
	'Ctenocephalus inaequalis' => 'Proc. biol. Soc. Washington, 53, 37.',


);

foreach ($citations as $name => $citation)
{

	$url = 'http://localhost/citation-matching/api_parser.php?';
	
	$parameters = array(
		'q' => $citation
	);
	
	$json = get($url . http_build_query($parameters));
	
	$doc = json_decode($json);
	
	if ($doc->status == 200)
	{
		
		$url = 'http://localhost/citation-matching/api_bhl.php';
		
		$json = post($url, $doc);

		$doc = json_decode($json);
		
		if ($doc)
		{
			if (isset($doc->text))
			{
				foreach ($doc->text as $pageid => $text)
				{
					$query_doc = new stdclass;
					$query_doc->ignorecase 	= true;
					$query_doc->needle 		= $name;
					$query_doc->haystack 	= $text;
					
					$url = 'http://localhost/citation-matching/api_text.php';
		
					$json = post($url, $query_doc);
					
					$result = json_decode($json);
					
					if ($result)
					{
						if ($result->total != 0)
						{
							file_put_contents($pageid . '.html', $result->html);
						}
					}
				}			
			}
		}
	}
}

?>
