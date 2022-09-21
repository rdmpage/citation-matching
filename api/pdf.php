<?php

ini_set("memory_limit","2048M");

// Get info from PDF


// Note that pdf parser can't handle Chinese PDFs well,
// e.g. http://www.scdwzz.com/admin/downfile.aspx?id=20123 the English text is mangled

error_reporting(E_ALL ^ E_WARNING);

require_once (dirname(dirname(__FILE__)) . '/vendor/autoload.php');
require_once (dirname(__FILE__) . '/api_utilities.php');


$doc = null;
$doc = http_post_endpoint(["url"]);

if (0)
{
	$doc = new stdclass;
	$doc->url = 'https://www.biotaxa.org/rce/article/view/45566';
	$doc->url = 'https://www.biotaxa.org/Zootaxa/article/view/zootaxa.5125.5.4/71559'; // PDF!
	$doc->url = 'https://kmkjournals.com/upload/PDF/REJ/30/ent30_4_413_429_Fedorenko_for_Inet.pdf';
	//$doc->url = 'https://www.researchgate.net/profile/Jerome-Constant/publication/358103845_First_record_of_the_lanternfly_genus_Limois_Stal_1863_in_Vietnam_with_a_new_species_L_sonlaensis_sp_nov_Hemiptera_Fulgoromorpha_Fulgoridae/links/61f00739dafcdb25fd4e9a05/First-record-of-the-lanternfly-genus-Limois-Stal-1863-in-Vietnam-with-a-new-species-L-sonlaensis-sp-nov-Hemiptera-Fulgoromorpha-Fulgoridae.pdf';
	//$doc->url = 'https://scholar.archive.org/work/djqknujmu5ae7b6udhdc64bt6a/access/wayback/https://vietnamscience.vjst.vn/index.php/vjste/article/download/339/325/1327';
	$doc->url = 'https://www.biotaxa.org/Zootaxa/article/view/zootaxa.1188.1.3';
	$doc->url = 'https://lasef.org/wp-content/uploads/BSEF/121-1/1868_Boilly.pdf'; // zoobank
	$doc->status = 404;
}


//----------------------------------------------------------------------------------------
function download_file($path,$fname){
	$options = array(
		CURLOPT_FILE => fopen($fname, 'w'),
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_MAXREDIRS => 10,
		CURLOPT_URL => $path,
		CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
		CURLOPT_TIMEOUT => 60,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; ImageFetcher/5.6; +http://images.weserv.nl/)',
	);
	
	//print_r($options);
	
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$return = curl_exec($ch);
	
	if ($return === false){
		$error = curl_error($ch);
		$errno = curl_errno($ch);
		curl_close($ch);
		unlink($fname);
		$error_code = substr($error,0,3);
		
		if($errno == 6){
			header('HTTP/1.1 410 Gone');
			header('X-Robots-Tag: none');
			header('X-Gone-Reason: Hostname not in DNS or blocked by policy');
			echo 'Error 410: Server could not parse the ?url= that you were looking for "' . $path . '", because the hostname of the origin is unresolvable (DNS) or blocked by policy.';
			echo 'Error: $error';
			die;
		}
		
		if(in_array($error_code,array('400','403','404','500','502'))){
			trigger_error('cURL Request error: '.$error.' URL: '.$path,E_USER_WARNING);
		}
		return array(false,$error);
	}else{
		curl_close($ch);
		return array(true,NULL);
	}
}

//----------------------------------------------------------------------------------------
function get_first_page($pdf_filename)
{
	// Parse PDF file and build necessary objects.
	$parser = new \Smalot\PdfParser\Parser();
	$pdf = $parser->parseFile($pdf_filename);
	
	$text = $pdf->getPages()[0]->getText();
	
	// clean text
	// some PDFs such as https://www.smujo.id/biodiv/article/download/11019/5730 are full of tabs
	$text = str_replace("\t", "", $text);
	
	// to do: handle cases where there is a cover page
	
	return $text;
}

//----------------------------------------------------------------------------------------

$doc->status = 404;

// fetch PDF
if (isset($doc->pdf))
{
	$path = $doc->pdf;
}
else
{
	$path = $doc->url;
}
$path = str_replace(' ','%20',$path);
$pdf_filename = tempnam(sys_get_temp_dir(), 'pdf_');
$curl_result = download_file($path, $pdf_filename);
if($curl_result[0] === false)
{
	$doc->status = 404;
}
else
{
	// do we have a PDF?
	
	// PDF sanity check
	$pdf_ok = true;
	
	$handle = fopen($pdf_filename, "rb");
	$file_start = fread($handle, 1024);  //<<--- as per your need 
	fclose($handle);
	
	$pdf_ok = false;
	
	if (preg_match('/^\s*%PDF/', $file_start ))
	{
		$pdf_ok = true;
	}
	else
	{
		$doc->message = "Not a PDF";
		$doc->status = 406;
	}
	
	if ($pdf_ok)
	{
		$doc->pdf = $doc->url;
	
		$doc->status = 200;
		
		$doc->text = get_first_page($pdf_filename);
		
		// try and extract a DOI
		if (preg_match('/((doi\s*:\s*|https?:\/\/(dx\.)?doi.org\/)(?<doi>10\.[0-9]{4,}(?:\.[0-9]+)*(?:\/|%2F)(?:(?![\"&\'])\S)+))/i', $doc->text, $m))
		{
			$doc->doi = strtolower($m['doi']);
			
			// cleaning
			// MDPI
			$doc->doi = preg_replace('/https:\/\/www.mdpi.com.*$/', '', $doc->doi);

			// ZooKeys
			$doc->doi = preg_replace('/https?:\/\/zookeys.*$/', '', $doc->doi);
		}
		
		// try and extract ZooBank
		if (preg_match('/zoobank.org(\/|:pub:)(?<id>[A-Z0-9]{8}(-[A-Z0-9]{4}){3}-[A-Z0-9]{12})/i', $doc->text, $m))
		{
			$doc->zoobank = strtolower($m['id']);
		}
		
	}
}

send_doc($doc);	

?>





