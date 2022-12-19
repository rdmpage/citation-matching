<?php

// Get metadata for a China DOI

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/HtmlDomParser.php');

use Sunra\PhpSimple\HtmlDomParser;

$doc = null;
$doc = http_post_endpoint(["DOI"]);

if (0)
{
	$doc = new stdclass;
	$doc->DOI = '10.3969/j.issn.1005-9628.2014.01.001';
}

$doc->status = 404;

if (isset($doc->DOI))
{
	$url = 'http://www.chinadoi.cn/portal/mr.action?doi=' . urlencode($doc->DOI);
	$html = get($url);
	
	if ($html != '')
	{
		$dom = HtmlDomParser::str_get_html($html);
	
		if ($dom)
		{
			foreach ($dom->find('tr') as $tr)
			{
				$key = '';
				$value = '';
						
				$count = 1;
		
				foreach ($tr->find('td[class=title1]') as $td)
				{
					if ($count % 2 == 1)
					{
						$key = $td->plaintext;
						$key = str_replace('&nbsp;', ' ', $key);
						$key = trim($key);
					}
					else
					{				
						$value = $td->plaintext;
						$value = str_replace('&nbsp;', ' ', $value);
						$value = trim($value);
						
						$value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
						
						// echo $key . '=' . $value . "\n";
				
						switch ($key)
						{
							case 'Volume':
							case 'Issue':
								$doc->{strtolower($key)} = $value;
								break;	
								
							case 'Year':
								$doc->issued = new stdclass;
								$doc->issued->{'date-parts'} = array();
								$doc->issued->{'date-parts'}[] = array((Integer)$value);
								break;
								
							case 'Journal：':
								$doc->{'container-title'} = $value;
								
								$issn = '';
								switch ($value)
								{
									case 'ATCA PALAEONTOLOGICA SINICA':
										$doc->ISSN[] = '0001-6616';
										break;
										
									case 'Journal of Biosafety':
										$doc->ISSN[] = '2095-1787';
										break;

									case 'Zoological Systematics':
										$doc->ISSN[] = '1000-0739';
										break;
								
									default:
										break;
								}
								break;
																
							case 'doi：':
								$doc->DOI = $value;
								break;
								
								
							case 'Title：':
								$doc->title = $value;
								
								if (!isset($doc->multi))
								{
									$doc->multi = new stdclass;
									$doc->multi->_key = new stdclass;
								}
								if (!isset($doc->multi->_key->title))
								{
									$doc->multi->_key->title  = new stdclass;
								}
								$doc->multi->_key->title->en = $value;
								break;
								
							case '题 名：':
								if (!isset($doc->multi))
								{
									$doc->multi = new stdclass;
									$doc->multi->_key = new stdclass;
								}
								if (!isset($doc->multi->_key->title))
								{
									$doc->multi->_key->title  = new stdclass;
								}
								$doc->multi->_key->title->zh = $value;
								break;
								
								
							case '第一作者：':
								if (preg_match('/\p{Han}+/u', $value))
								{
									$author = new stdclass;
									$author->literal = $value;
									$doc->author = array($author);
								}
								break;														
				
							default:
								break;
						}
					}
					$count++;
				}
			}
			
			$doc->status = 200;
		}
	}
}

send_doc($doc);	

?>
