<?php

// parse a full citation string 
require_once (dirname(__FILE__) . '/api_utilities.php');

$doc = null;

if ($_SERVER['REQUEST_METHOD'] == 'GET')
{
	$doc = http_get_endpoint(["q"]);
}
else
{
	$doc = http_post_endpoint(["q"]);
}

$doc->status = 404;

if (isset($doc->q))
{
	$url = 'http://localhost/citation-parsing/api.php?text=' . urlencode($doc->q);
		
	$json = get($url);
	
	$obj = json_decode($json);
	if ($obj)
	{
		$doc->status = 200;
		
		foreach ($obj[0] as $k => $v)
		{
			$doc->{$k} = $v;
		}		
	}
	else
	{
		// badness
		$doc->status = 400;
	}
}

send_doc($doc);	

?>
