<?php

// BHL queries using triple store

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/db.php');
require_once (dirname(__FILE__) . '/external.php');
require_once (dirname(dirname(__FILE__)) . '/sparql.php');

$required = array('container-title', 'volume', 'page');
$doc = http_post_endpoint($required);

// can we d this using triple store?
// BHL title	
if (isset($doc->ISSN))
{
	$title_id = get_bhl_title_from_issn($doc->ISSN[0]);
	if (count($title_id) > 0)
	{
		$doc->BHLTITLEID = $title_id;
	}				
}

// hack
if (isset($doc->{'container-title'}) && !isset($doc->BHLTITLEID))
{
	$titles = get_bhl_title_from_text($doc->{'container-title'});
	if (count($titles) > 0)
	{
		$doc->BHLTITLEID = $titles;
	}

}

// Find page(s)

if (isset($doc->BHLTITLEID))
{
	// Find BHL page(s) using triple store

	$page = array();

	if (isset($doc->BHLTITLEID))
	{
		foreach ($doc->BHLTITLEID as $TitleID)
		{		
			$results = get_page_from_triple($TitleID, $doc->volume, $doc->page);
		
			if (count($results) > 0)
			{
				foreach ($results as $result)
				{
					$page[] = str_replace('https://www.biodiversitylibrary.org/page/', '', $result->id);
				}		
			}		
		}
	}

	if (count($page) > 0)
	{
		$doc->BHLPAGEID = $page;
	}

	// parts
	if (isset($doc->BHLPAGEID))
	{
		foreach ($doc->BHLPAGEID as $pageid)
		{
			$results = get_part_from_bhl_page($pageid);
		
			//print_r($results);
		
			if (count($results) > 0)
			{
				foreach ($results as $result)
				{
					if (isset($result->isPartOf))
					{
						foreach ($result->isPartOf as $part)
						{
							if (!isset($doc->BHLPART))
							{
								$doc->BHLPART = array();
							}
							$doc->BHLPART[] = $part;
						}
					}
				}		
			}		
		}
	}
	
	// text
	if (isset($doc->BHLPAGEID))
	{
		foreach ($doc->BHLPAGEID as $pageid)
		{
			$text = get_bhl_page_text($pageid);
			if ($text != '')
			{
				if (!isset($doc->text))
				{
					$doc->text = array();
				}
			
				$text = mb_convert_encoding($text, 'UTF-8', mb_detect_encoding($text));
			
				$doc->text[$pageid] = $text;
			
				//$doc->encoding = mb_detect_encoding($text);
			}	
		}
	}	
}
else
{
	// Try other sources
	if (isset($doc->ISSN))
	{
	
		$results = get_page_from_triple_issn($doc->ISSN[0], $doc->volume, $doc->page);
	
		//print_r($results);
		
		if (count($results) > 0)
		{
			foreach ($results as $result)
			{
				//$page[] = str_replace('https://www.biodiversitylibrary.org/page/', '', $result->id);
				
				if (isset($result->isPartOf))
				{
					if (isset($result->isPartOf[0]->name))
					{
						$doc->title = $result->isPartOf[0]->name;
					}
					//$doc->work = array($result->isPartOf[0]);
				
				}
				
				if (isset($result->text))
				{
					if (!isset($doc->text))
					{
						$doc->text = array();
					}
					$doc->text[$result->name] = $result->text;
					
					if (!isset($doc->image))
					{
						$doc->image = array();
					}
					
					$image_url = $result->id;
					$image_url = str_replace('/details/', '/download/', $image_url);
					$image_url .= '.jpg';
					
					$doc->image[$result->name] = $image_url;
					
					
				}								
			}		
		}		

	}

}




send_doc($doc);	

?>
