<?php

// Get info from web page of article

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(dirname(__FILE__)) . '/HtmlDomParser.php');

use Sunra\PhpSimple\HtmlDomParser;

$doc = null;
$doc = http_post_endpoint(["url"]);

if (0)
{
	$doc = new stdclass;
	$doc->url = 'https://www.biotaxa.org/rce/article/view/45566';
	$doc->url = 'https://www.biotaxa.org/Zootaxa/article/view/zootaxa.5125.5.4/71559'; // PDF!
	$doc->url = 'https://cdnsciencepub.com/doi/abs/10.1139/cjes-2020-0190';
	$doc->url = 'https://www.biotaxa.org/Zootaxa/article/view/zootaxa.1188.1.3';
	$doc->url = 'https://www.scielo.br/j/ni/a/dGx5NWWPDmgjRwX3QFyb64m/';
	$doc->status = 404;
}

$doc->status = 404;


// Does this look like a PDF?

if (!isset($doc->pdf))
{
	if (preg_match('/pdf/', $doc->url))
	{
		$doc->pdf = $doc->url;
	}
}

if (!isset($doc->pdf))
{
	if (preg_match('/download/', $doc->url))
	{
		$doc->pdf = $doc->url;
	}
}

// Can we get DOI from URL structure?
if (!isset($doc->doi))
{
	if (preg_match('/doi\/pdf\/(?<doi>10\.[0-9]{4,}(?:\.[0-9]+)*(?:\/|%2F)(?:(?![\"&\'])\S)+)$/', $doc->url, $m))
	{
		$doc->doi = $m['doi'];
		$doc->doi = preg_replace('/\?.*$/', '', $doc->doi);
	}
}

if (!isset($doc->doi))
{
	if (preg_match('/doi\/(abs\/)?(?<doi>10\.[0-9]{4,}(?:\.[0-9]+)*(?:\/|%2F)(?:(?![\"&\'])\S)+)(\/pdf)?$/', $doc->url, $m))
	{
		$doc->doi = $m['doi'];
	}
}

// Looks like a website and we don't have a DOI
if (!isset($doc->pdf) || !isset($doc->doi))
{
	$html = get($doc->url, "text/html");
		
	if ($html != '')
	{
		//echo $html;
		
		// keep things manageable by looking at the start of the hTML
		$html = substr($html, 0, 20000);
		//echo $html;

		$dom = HtmlDomParser::str_get_html($html);
	
		if ($dom)
		{	
			// meta tags
			foreach ($dom->find('meta') as $meta)
			{			
				//echo $meta->name . "\n";
			
				// DOI
				if (isset($meta->name) && ($meta->content != ''))
				{
					switch ($meta->name)
					{				
						case 'citation_doi':
							$doi = trim($meta->content);
							$doc->doi = $doi;
							break;		
							
						case 'citation_pdf_url':
							$doc->pdf = trim($meta->content);
							break;					
										
						case 'DC.identifier':
							$doi = trim($meta->content);
							$doi = str_replace('info:doi/', '', $doi);
							$doc->doi = $doi;
							break;	
						
						// https://cdnsciencepub.com/doi/abs/10.1139/cjes-2020-0190
						case 'dc.Identifier':
							if (isset($meta->scheme) && ($meta->scheme == 'doi'))
							{
								$doi =trim($meta->content);
								$doc->doi = $doi;	
							}								
							break;					
									
						// https://www.thebhs.org/publications/the-herpetological-journal/volume-32-number-1-january-2022/3430-05-i-acanthosaura-meridiona-i-sp-nov-squamata-agamidae-a-new-short-horned-lizard-from-southern-thailand	
						case 'description':
							if (preg_match('/(DOI:\s+)?https:\/\/doi.org\/(?<doi>[^\s]+)/', $meta->content, $m))
							{
								$doc->doi = $m['doi'];
							}
							break;				

						default:
							break;
					}
				}
			}
		}
		
		if (!isset($doc->doi))
		{
			// OK, something more journal/site specific...
			
			if (preg_match('/zoolstud.sinica.edu.tw/', $doc->url))
			{
				foreach ($dom->find('sup big') as $big)
				{
					if (preg_match('/doi:(?<doi>.*)/', $big->plaintext, $m))
					{
						$doc->doi = $m['doi'];
					}
				}
			}
			
			if (preg_match('/www.ahr-journal.com/', $doc->url))
			{
				foreach ($dom->find('span[id=LbDOI]') as $big)
				{
					if (preg_match('/(?<doi>10\..*)\b/', $big->plaintext, $m))
					{
						$doc->doi = $m['doi'];
					}
				}
			}
		}
			
		// ZooBank
		if (preg_match('/zoobank.org\/References\/(?<id>[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12})/i', $doc->url, $m))
		{
			$doc->zoobank = strtolower($m['id']);
		
			foreach ($dom->find('tr th[class=entry_label]') as $th)
			{
				switch (trim($th->plaintext))
				{
					case 'DOI:':
						$doi = trim($th->next_sibling()->plaintext);
						$doi = preg_replace('/https?:\/\/(dx\.)?doi.org\//i', '', $doi);
						$doc->doi = $doi;	
						break;
	
					default:
						break;
				}
			}

		}

	}
}

if (isset($doc->doi) || isset($doc->pdf))
{
	$doc->status = 200;
	
	if (isset($doc->doi))
	{
		$doc->doi = strtolower($doc->doi);
	}
}

send_doc($doc);	

?>





