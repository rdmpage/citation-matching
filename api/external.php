<?php

// External services, such as BHL

require_once (dirname(__FILE__) . '/api_utilities.php');

//----------------------------------------------------------------------------------------
// Given a structured citation with container-title, volume, and page, can we match to
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

//----------------------------------------------------------------------------------------
// Get BHL item (caching to disk)
function get_bhl_item($ItemID)
{
	global $config;
	
	$obj = null;
	
	$filename = $config['cache'] . '/item-' . $ItemID . '.json';
	
	if (!file_exists($filename))
	{
		$parameters = array(
			'op' 		=> 'GetItemMetadata',
			'itemid' => $ItemID,
			'pages' => 'true',
			'ocr' => 'false',
			'parts' => 'true',
			'apikey'	=> $config['BHL_API_KEY'],
			'format'	=> 'json'
		);
	
		$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);
	
		$json = get($url);
		
		file_put_contents($filename, $json);
	}
	
	$json = file_get_contents($filename);
	
	$obj = json_decode($json);
	
	return $obj;
}

//----------------------------------------------------------------------------------------
// Extract page number/label from BHL PageNumbers structure
function get_page_number($PageNumbers)
{
	$value = '';
	
	if (isset($PageNumbers[0]->Number) && ($PageNumbers[0]->Number != ''))
	{
		$value = $PageNumbers[0]->Number;
		$value = preg_replace('/Page%/', '', $value);
		$value = preg_replace('/(Pl\.?(ate)?)%/', '$1 ', $value);
	}	
	
	return $value;
}

//----------------------------------------------------------------------------------------
// Find BHL page(s) in Item with specific page number (i.e., the page with label "4")
// or the 4th page in the item ($page_is_position = true)
function bhl_pages_with_number($ItemID, $target_page, $page_is_position = false)
{
	$bhl_pages = array();
	
	$item = get_bhl_item($ItemID);
	if ($item)
	{
		if ($page_is_position)
		{
			$bhl_pages[] = $item->Result->Pages[$target_page]->PageID;
		}
		else
		{
			$n = count($item->Result->Pages);
			for ($i = 0; $i < $n; $i++)
			{
				$page_label = get_page_number($item->Result->Pages[$i]->PageNumbers);
			
				if ($page_label == $target_page)
				{
					$bhl_pages[] = $item->Result->Pages[$i]->PageID;
				}
			}
		}
	}

	return $bhl_pages;
}	

//----------------------------------------------------------------------------------------
// Find BHL ItemID from Internet Archive id
function get_bhl_item_from_ia($ia)
{
	global $config;
	
	$ItemID = 0;

	$parameters = array(
		'op' 		=> 'GetItemByIdentifier',
		'type' 		=> 'ia',
		'value' 	=> $ia,
		'apikey'	=> $config['BHL_API_KEY'],
		'format'	=> 'json'
	);

	$url = 'https://www.biodiversitylibrary.org/api2/httpquery.ashx?' . http_build_query($parameters);

	$json = get($url);
	
	$obj = json_decode($json);
	
	if ($obj->Status == 'ok')
	{
		$ItemID = $obj->Result->ItemID;
	}

	return $ItemID;
}


?>
