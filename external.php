<?php

// External services, such as BHL

require_once (dirname(__FILE__) . '/api_utilities.php');

//----------------------------------------------------------------------------------------
// Given a structured citation with container-ttiel, volume, and page, can we match to
// one or more BHL pages using BHL's OpenURL query?
function find_bhl_page($obj)
{

	if (
		isset($obj->BHLTITLEID)
		&& isset($obj->volume)
		&& isset($obj->page)
	)
	{

		$parameters = array(
	
			'volume' 	=> $obj->volume,
			'spage'		=> $obj->page,
			'pid'		=> 'title:' . $obj->BHLTITLEID[0],
			'format' 	=> 'json'
		);
	
	
		$url = 'https://www.biodiversitylibrary.org/openurl?';
		
		$url .= http_build_query($parameters);
		
		$json = get($url);
		
		$response = json_decode($json);
		
		if ($response)
		{
			foreach ($response->citations as $citation)
			{
			
				if (!isset($obj->BHLPAGEID))
				{
					$obj->BHLPAGEID = array();
				}
				$obj->BHLPAGEID[] = str_replace('https://www.biodiversitylibrary.org/page/', '', $citation->Url);
			}
		}

	}

	return $obj;
}

//----------------------------------------------------------------------------------------
function get_bhl_page_text($pageid)
{
	global $config;
	
	$text = '';
	
	$filename = $config['cache'] . '/' . $pageid . '.json';
	
	if (!file_exists($filename))
	{
		$parameters = array(
			'op' 		=> 'GetPageMetadata',
			'pageid'	=> $pageid,
			'ocr'		=> 't',
			'names'		=> 't',
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
		$json = get($url);
		
		file_put_contents($filename, $json);
	}
	
	$json = file_get_contents($filename);
	
	$obj = json_decode($json);
	
	if (isset($obj->Result->OcrText))
	{
		$text = $obj->Result->OcrText;
		
		// remove double lines
		$text = preg_replace('/\n\n/', "\n", $text);
	}

	return $text;
}

?>
