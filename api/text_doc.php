<?php

// Search for needle in haystack for text in a doc

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/textsearch.php');


$required = array('name', 'text');
$doc = http_post_endpoint($required);

if ($doc->status == 200)
{
	$hit_count = 0;

	$doc->hits = array();

	foreach ($doc->text as $id => $text)
	{
		$hits = find_in_text(
			$doc->name, 
			$text, 
			isset($doc->ignorecase) ? $doc->ignorecase : true,
			isset($doc->maxerror) ? $doc->maxerror : 2	
			);
			
		// if we have hits store them
		if ($hits->total > 0)
		{	
			$doc->hits[$id] = $hits;			
			$hit_count += $hits->total;
		}
	}
	
	$doc->status = ($hit_count > 0 ? 200 : 404);
}

send_doc($doc);	

?>
