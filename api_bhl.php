<?php

require_once (dirname(__FILE__) . '/api_utilities.php');
require_once (dirname(__FILE__) . '/db.php');
require_once (dirname(__FILE__) . '/external.php');


$required = array('container-title', 'volume', 'page');
$doc = http_post_endpoint($required);


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
	switch ($doc->{'container-title'})
	{
		case 'Exot. Micr.':
		case 'Exotic Microlep.':
		case 'Exot. Microlepid.':
		case 'Exotic microlepidoptera':
			$doc->BHLTITLEID = array(9241);
			break;			
	
		case 'Genera Insectorum':
			$doc->BHLTITLEID = array(45481);
			break;
		
		default:
			break;
	}	
}

// BHL page
$doc = find_bhl_page($doc);
	

send_doc($doc);	

?>
