<?php

// External services, such as BHL

require_once (dirname(__FILE__) . '/api_utilities.php');

//----------------------------------------------------------------------------------------
// Given a structured citation with container-ttiel, volume, and page, can we match to
// one or more BHL pages?
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

?>
