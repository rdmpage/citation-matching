<?php

// Wikidata to CSL

error_reporting(E_ALL);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');

//----------------------------------------------------------------------------------------
// Fetch native JSON for Wikidata item.
function get_one($id)
{
	$json = get('https://www.wikidata.org/w/api.php?action=wbgetentities&ids=' . $id . '&format=json');
	return $json;
}

//----------------------------------------------------------------------------------------
function literal_value_simple($obj)
{
	$result = '';
	
	foreach ($obj as $k => $v)
	{
		if ($v->rank == 'normal')
		{
			$result = $v->mainsnak->datavalue->value;
		}
	}

	return $result;

}

//----------------------------------------------------------------------------------------
function literal_value_multilingual($obj)
{
	$result = array();
	
	foreach ($obj as $k => $v)
	{
		if ($v->rank == 'normal')
		{
			$language = $v->mainsnak->datavalue->value->language;
			$value = $v->mainsnak->datavalue->value->text;
			
			if (!isset($result[$language]))
			{
				$result[$language] = array();
			}
			$result[$language][] = $value;
		}
	}

	return $result;
}

//----------------------------------------------------------------------------------------
// Get just one date
function date_value($obj)
{
	$result = array();
	
	foreach ($obj as $k => $v)
	{
		if (($v->rank == 'normal') && (count($result) == 0))
		{
			$value = $v->mainsnak->datavalue->value;
			
			if (preg_match('/\+(?<year>[0-9]{4})-00-00/', $value->time, $m))
			{
				$result[] = (Integer)$m['year'];
			}
			else
			{
				if (preg_match('/\+(?<year>[0-9]{4})-(?<month>[0-1][0-9])-00/', $value->time, $m))
				{
					$result[] = (Integer)$m['year'];
					$result[] = (Integer)$m['month'];
				}
				else
				{
					if (preg_match('/\+(?<year>[0-9]{4})-(?<month>[0-1][0-9])-(?<day>[0-3][0-9])/', $value->time, $m))
					{
						$result[] = (Integer)$m['year'];
						$result[] = (Integer)$m['month'];
						$result[] = (Integer)$m['day'];
					}
				}
			}
		}
	}

	return $result;
}

//----------------------------------------------------------------------------------------
function ordered_simple_literal($obj)
{
	$result = array();
	
	foreach ($obj as $k => $v)
	{
		if ($v->rank == 'normal')
		{
			$value = $v->mainsnak->datavalue->value;

			if (isset($v->qualifiers))
			{
				if (isset($v->qualifiers->{'P1545'}))
				{
					$order = $v->qualifiers->{'P1545'}[0]->datavalue->value;
					$result[$order] = $value;
				}
			
			}

		}
	}

	return $result;
}

//----------------------------------------------------------------------------------------
// Get serial order qualifier for a given claim
function claim_serial_order($v)
{
	$order = 0;
	
	if ($v->rank == 'normal')
	{
		if (isset($v->qualifiers))
		{
			if (isset($v->qualifiers->{'P1545'}))
			{
				$order = $v->qualifiers->{'P1545'}[0]->datavalue->value;
			}
		
		}

	}

	return $order;
}

//----------------------------------------------------------------------------------------
// get journal name(s) and ISSN(s), if any
function get_container_info($id, &$obj)
{
	$json = get_one($id);
	$wd = json_decode($json);
	
	foreach ($wd->entities->{$id}->claims as $k => $claim)
	{
		switch ($k)
		{
			// ISSN
			case 'P236':
				if (!isset($obj->ISSN))
				{
					$obj->ISSN = array();
				}
				foreach ($claim as $c)
				{
					$obj->ISSN[] = $c->mainsnak->datavalue->value;
				}			
				break;
				
			// title
			case 'P1476': 
				$values = literal_value_multilingual ($claim);
				
				// print_r($values);
			
				if (!isset($obj->multi))
				{
					$obj->multi = new stdclass;
					$obj->multi->_key = new stdclass;
				}
			
				$obj->multi->_key->{'container-title'} = new stdclass;
			
				foreach ($values as $language => $text)
				{
					$obj->multi->_key->{'container-title'}->{$language} = $text[0];
				
					// use one value as title
					if (!isset($obj->{'container-title'}))
					{
						$obj->{'container-title'} = $text[0];
					}
				}			
				break;
				
			// ISO 4 abbreviation
			case 'P1160':
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->journalAbbreviation = $value;
				}					
				break;
			
			default:
				break;
		}
	}
}


//----------------------------------------------------------------------------------------
// Get author name and any identifiers
function get_author_info($id, $order, &$obj)
{
	$json = get_one($id);
	$wd = json_decode($json);
	
	if (!isset($obj->authors))
	{
		$obj->authors = array();
	}
	
	$author = new stdclass;
	
	$author->WIKIDATA = $id;
	
	$author->literal = "";
	
	// name is a label
	
	// we should have an English label...
	if ($author->literal == '')
	{
		if (isset($wd->entities->{$id}->labels->en))
		{
			$author->literal = $wd->entities->{$id}->labels->en->value;
		}
	}

	// Chinese
	if ($author->literal == '')
	{
		if (isset($wd->entities->{$id}->labels->zh))
		{
			$author->literal = $wd->entities->{$id}->labels->zh->value;
		}
	}

	// Japanese
	if ($author->literal == '')
	{
		if (isset($wd->entities->{$id}->labels->ja))
		{
			$author->literal = $wd->entities->{$id}->labels->ja->value;
		}
	}
	
	// If we have nothing at this point...?
	
	
	if ($author->literal == '')
	{
		$author->literal = '[unknown]';
	}
		
	foreach ($wd->entities->{$id}->claims as $k => $claim)
	{
		
		switch ($k)
		{
			case 'P18':
				$author->thumbnailUrl = 'https://commons.wikimedia.org/w/thumb.php?f=' . literal_value_simple($claim) . '&w=200';
				break;
						
			case 'P496':
				$author->ORCID = 'https://orcid.org/' . literal_value_simple($claim);
				break;	
				
			case 'P2038':
				$author->RESEARCHGATE = 'https://www.researchgate.net/profile/' . literal_value_simple($claim);
				break;							
			
			default:
				break;
		}
	}

	$obj->authors[$order] = $author;
}

//----------------------------------------------------------------------------------------
function wikidata_to_csl($id, $hit_servers=false)
{
	$wikidata_to_csl = array(
		'P304' 	=> 'page',
		'P356' 	=> 'DOI',
		'P1184' => 'HANDLE',
		'P888' 	=> 'JSTOR',
		'P5315' => 'BIOSTOR',
		'P698' 	=> 'PMID',
		'P932'	=> 'PMC',
		'P433' 	=> 'issue',
		'P478' 	=> 'volume',
		'P1476' => 'title',	
		'P577' 	=> 'issued',	
		'P2093' => 'author',	
	);

	$json = get_one($id);
	$wd = json_decode($json);
	
	if (!$wd)
	{
		return null;
	}
	
	$obj = new stdclass;
	$obj->id = $id;
	
	if (!isset($wd->entities->{$id}->claims))
	{
		return null;
	}

	foreach ($wd->entities->{$id}->claims as $k => $claim)
	{
		switch ($k)
		{
			// instance 
			case 'P31':
				$instances = array();
				foreach ($claim as $c)
				{
					$instances[] = $c->mainsnak->datavalue->value->id;		
				}
				
				$type = '';
				
				if ($type == '')
				{
					if (in_array('Q13442814', $instances)) // scholarly article
					{
						$type = 'article-journal';
					}
				}
				if ($type == '')
				{

					if (in_array('Q18918145', $instances)) // academic journal article
					{
						$type = 'article-journal';
					}
				}
				if ($type == '')
				{
					if (in_array('Q191067', $instances)) // article
					{
						$type = 'article-journal';
					}
				}
				if ($type == '')
				{
					if (in_array('Q47461344', $instances)) // written work
					{
						$type = 'book';
					}
				}
				if ($type == '')
				{
					if (in_array('Q571', $instances)) // book
					{
						$type = 'book';
					}
				}
				if ($type == '')
				{					
					if (in_array('Q3331189', $instances)) // version, edition, or translation
					{
						$type = 'book';
					}
				}
				if ($type == '')
				{
					if (in_array('Q1980247', $instances)) // chapter
					{
						$type = 'chapter';
					}
				}

				if ($type == '')
				{
					if (in_array('Q1266946', $instances)) // thesis
					{
						$type = 'thesis';
					}
				}

				if ($type == '')
				{
					if (in_array('Q187685', $instances)) // doctoral thesis
					{
						$type = 'thesis';
					}
				}
				
				if ($type == '')
				{
					if (in_array('Q732577', $instances)) // publication
					{
						$type = 'book';
					}
				}
				
				
				if ($type == '')
				{
					// not somthing we want
					return null;
				}
				
				$obj->type = $type;
				break;
	
			// simple values
			case 'P304':
			case 'P433':
			case 'P478':
		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = $value;
				}
		
				break;
			
			// DOI
			case 'P356':		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = strtolower($value);
				}		
				break;	

			// Handle
			case 'P1184':		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = strtolower($value);
				}		
				break;	
				
			// JSTOR
			case 'P888':		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = strtolower($value);
				}		
				break;		

			// PMID
			case 'P698':		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = strtolower($value);
				}		
				break;		

			// PMC 
			case 'P932':		
				$value = literal_value_simple($claim);
				if ($value != '')
				{
					$obj->{$wikidata_to_csl[$k]} = strtolower($value);
				}		
				break;		
			
			// title
			case 'P1476':
				$values = literal_value_multilingual ($claim);
			
				// print_r($values);
			
				if (!isset($obj->multi))
				{
					$obj->multi = new stdclass;
					$obj->multi->_key = new stdclass;
				}
			
				$obj->multi->_key->{$wikidata_to_csl[$k]} = new stdclass;
			
				foreach ($values as $language => $text)
				{
					$obj->multi->_key->{$wikidata_to_csl[$k]}->{$language} = $text[0];
				
					// use one value as title
					if (!isset($obj->{$wikidata_to_csl[$k]}))
					{
						$obj->{$wikidata_to_csl[$k]} = $text[0];
					}
				}
			
				break;
			
			// publication date
			case 'P577':
				$obj->{$wikidata_to_csl[$k]} = new stdclass;
				$obj->{$wikidata_to_csl[$k]}->{'date-parts'} = array();
				$obj->{$wikidata_to_csl[$k]}->{'date-parts'}[] = date_value($claim);	
				break;
			
			// author as string
			case 'P2093':
				$authorstrings = ordered_simple_literal($claim);
			
				if (!isset($obj->authors))
				{
					$obj->authors = array();
				}
			
				foreach ($authorstrings as $order => $string)
				{
					$author = new stdclass;
					$author->literal = $string;
			
					$obj->authors[(Integer)$order] = $author;
				}			
				break;
					
			// author as thing
			case 'P50':
				foreach ($claim as $c)
				{
					$order = claim_serial_order($c);
			
					$id = $c->mainsnak->datavalue->value->id;	
					get_author_info($id, $order, $obj);
				}		
				break;
		
			// container 
			case 'P1433':
				$mainsnak = $claim[0]->mainsnak;			
				$container_id = $mainsnak->datavalue->value->id;			
				get_container_info($container_id, $obj);			
				break;
								
			// BioStor
			case 'P5315':
				$value = literal_value_simple($claim);
				
				if ($value != '')
				{
					$obj->BIOSTOR = $value;
								
					if ($hit_servers)
					{
						$ia_id = 'biostor-' . $value;
				
						// could use simple rule but I don't have all of BioStor in IA yet
						$ia_url = 'https://archive.org/metadata/' . $ia_id;
				
						$ia_json = get($ia_url);
				
						$ia_obj = json_decode($ia_json);
						if ($ia_obj)
						{
							$pdf_name = '';
							if (isset($ia_obj->files))
							{
								foreach ($ia_obj->files as $file)
								{
									if ($file->format == 'Text PDF')
									{
										// PDF
										$link = new stdclass;
										$link->URL = 'https://archive.org/download/' . $ia_id . '/' . $file->name;
										$link->{'content-type'} = 'application/pdf';
							
										// guess the thumbnail
										$link->thumbnailUrl = 'https://archive.org/download/' . $ia_id . '/page/cover_thumb.jpg';
				
										if (!isset($obj->link))
										{
											$obj->link = array();
										}
										$obj->link[] = $link;

									}						
								}
							}
						}
					}					
				}			
				break;
		
			// PDF			
			case 'P724': // Internet Archive
				$value = literal_value_simple($claim);
				
				if ($value != '')
				{
					// We can't always rely on simple rules as some archives (e.g. PubMed Central)
					// have their own rules for files
					
					if ($hit_servers)
					{

									
						if (preg_match('/pubmed-PMC/', $value))
						{
							$ia_url = 'https://archive.org/metadata/' . $value;
					
							$ia_json = get($ia_url);
					
							$ia_obj = json_decode($ia_json);
							if ($ia_obj)
							{
								$pdf_name = '';
								foreach ($ia_obj->files as $file)
								{
									if ($file->format == 'Text PDF')
									{
										// PDF
										$link = new stdclass;
										$link->URL = 'https://archive.org/download/' . $value . '/' . $file->name;
										$link->{'content-type'} = 'application/pdf';
								
										// guess the thumbnail
										$link->thumbnailUrl = 'https://archive.org/download/' . $value . '/page/cover_thumb.jpg';
					
										if (!isset($obj->link))
										{
											$obj->link = array();
										}
										$obj->link[] = $link;

									}						
								}
							}
						}
						else
						{
							$link = new stdclass;
							$link->URL = 'https://archive.org/download/' . $value . '/' . $value . '.pdf';
							$link->{'content-type'} = 'application/pdf';
					
							// my hack
							$link->thumbnailUrl = 'https://archive.org/download/' . $value . '/page/cover_thumb.jpg';
					
							if (!isset($obj->link))
							{
								$obj->link = array();
							}
							$obj->link[] = $link;
						}
					}
				}
				break;
				
			case 'P953': // fulltext 
				foreach ($claim as $c)
				{
					$link = new stdclass;
					// $link->URL = $c->mainsnak->datavalue->value->value;
					
					if (isset($c->qualifiers))
					{
						// PDF?
						if (isset($c->qualifiers->{'P2701'}))
						{
							if ($c->qualifiers->{'P2701'}[0]->datavalue->value->id == 'Q42332')
							{
								$link->{'content-type'} = 'application/pdf';
							};
						}
						
						// Archived?
						if (isset($c->qualifiers->{'P1065'}))
						{
							$link->URL = $c->qualifiers->{'P1065'}[0]->datavalue->value;
							// direct link to PDF
							$link->URL = str_replace("/http", "if_/http", $link->URL);
						}						
					}
					
					if (isset($link->URL) && (isset($link->{'content-type'}) && $link->{'content-type'} == 'application/pdf'))
					{					
						if (!isset($obj->link))
						{
							$obj->link = array();
						}
						$obj->link[] = $link;
					
					}
				}		
				break;
			
	
			default:
				break;
		}
	}

	// post process

	// create ordered list of authors
	if (isset($obj->authors))
	{
		// print_r($obj->authors);

		$obj->author = array();

		ksort($obj->authors, SORT_NUMERIC);
		foreach ($obj->authors as $author)
		{
			$obj->author[] = $author;
		}

		unset($obj->authors);
		
		// post process name strings
		$n = count($obj->author);
		for ($i = 0; $i < $n; $i++)
		{
			// CSL PHP needs atomised names :(
			if (!isset($obj->author[$i]->family))
			{
				// We need to handle author names where there has been a clumsy attempt
				// (mostly by me) to include multiple language strings
			
				// 大橋広好(Hiroyoshi Ohashi)
				// 韦毅刚/WEI Yi-Gang
				if (preg_match('/^(.*)\s*[\/|\(]([^\)]+)/', $obj->author[$i]->literal, $m))
				{
					// print_r($m);
					
					if (preg_match('/\p{Han}+/u', $m[1]))
					{
						$obj->author[$i]->literal = $m[2];									
					}
					if (preg_match('/\p{Han}+/u', $m[2]))
					{
						$obj->author[$i]->literal = $m[1];									
					}
					
				}							
			
				$parts = preg_split('/,\s+/', $obj->author[$i]->literal);
				
				if (count($parts) == 2)
				{
					$obj->author[$i]->family = $parts[0];
					$obj->author[$i]->given = $parts[1];
				}
				else
				{
					$parts = preg_split('/\s+/', $obj->author[$i]->literal);
					
					if (count($parts) > 1)
					{
						$obj->author[$i]->family = array_pop($parts);
						$obj->author[$i]->given = join(' ', $parts);
					}
					
				}
			
			}
		}
		
	}
	
	return $obj;

}

?>
