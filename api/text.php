<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/textsearch.php');


$required = array('needle', 'haystack');
$doc = http_post_endpoint($required);

// do stuff here


$doc = find_in_text(
	$doc->needle, 
	$doc->haystack, 
	isset($doc->ignorecase) ? $doc->ignorecase : true,
	isset($doc->maxerror) ? $doc->maxerror : 2	
	);
	
	
// Was there a problem with the text search?
if (isset($doc->message))
{
	$doc->status = 400;
}
else
{
	$doc->status = ($doc->total > 0 ? 200 : 404);
}

send_doc($doc);	

?>
