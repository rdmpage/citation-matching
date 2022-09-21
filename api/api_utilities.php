<?php

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');

//----------------------------------------------------------------------------------------
function get($url, $format_type = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);	
	
	//curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	
	$headers = array();
	
	if ($format_type != '')
	{
		$headers[] = "Accept: " . $format_type;
		
		if ($format_type == 'text/html')
		{
			// play nice
			$headers[] = "Accept-Language: en-gb";
			$headers[] = "User-agent: Mozilla/5.0 (iPad; U; CPU OS 3_2_1 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Mobile/7B405";
			
			// Cookies 
			curl_setopt($ch, CURLOPT_COOKIEJAR, sys_get_temp_dir() . '/cookies.txt');
			curl_setopt($ch, CURLOPT_COOKIEFILE, sys_get_temp_dir() . '/cookies.txt');	
		}
	}
	
	//print_r($headers);
	
	if (count($headers) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
	
	$header = substr($response, 0, $info['header_size']);
	//echo $header;
	
	$content = substr($response, $info['header_size']);
	
	curl_close($ch);
	
	
	
	return $content;
}

//----------------------------------------------------------------------------------------
function post($url, $data = '', $content_type = 'application/json; charset=utf-8')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	
	// data needs to be a string
	if ($data != '')
	{
		if (gettype($data) != 'string')
		{
			$data = json_encode($data);
		}	
	}	
	
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	
	$headers = array();
	
	if ($content_type != '')
	{
		$headers[] = "Content-type: " . $content_type;
	}
	
	if (count($headers) > 0)
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	}
	
	$response = curl_exec($ch);
	if($response == FALSE) 
	{
		$errorText = curl_error($ch);
		curl_close($ch);
		die($errorText);
	}
	
	$info = curl_getinfo($ch);
	$http_code = $info['http_code'];
		
	curl_close($ch);
	
	return $response;
}

//----------------------------------------------------------------------------------------
function http_get_endpoint($required_parameters = array())
{
	$doc = new stdclass;
	
	if (count($_GET) == 0)
	{
		$doc->status = 400;
		$doc->message = "No GET parameters";
		send_doc($doc);	
	}
	else
	{
		foreach ($_GET as $k => $v)
		{
			$doc->{$k} = $v;
			
			if (($key = array_search($k, $required_parameters)) !== false) {
    			unset($required_parameters[$key]);
			}
		}
		
		if (count($required_parameters) > 0)
		{
			$doc = new stdclass;
			$doc->status = 400;
			$doc->message = "Missing GET parameter(s): " . join(", ", $required_parameters);
			send_doc($doc);				
		}
		
		$doc->status = 200;
	}
	
	return $doc;
}

//----------------------------------------------------------------------------------------
function http_post_endpoint($required_parameters = array())
{
	$content = file_get_contents('php://input');
	
	if ($content == '')
	{
		$doc = new stdclass;
		$doc->status = 400;
		$doc->message = "Empty POST body";		
	}
	else
	{
		$doc = json_decode($content);
	
		if (json_last_error() != JSON_ERROR_NONE)
		{
			$doc = new stdclass;
			$doc->status = 400;
			$doc->message = "Error parsing JSON: " . json_last_error_msg();
	
			send_doc($doc);	
		}
		else
		{
			// check we have required parameters, if any
			foreach ($doc as $k => $v)
			{			
				if (($key = array_search($k, $required_parameters)) !== false) {
					unset($required_parameters[$key]);
				}
			}
		
			if (count($required_parameters) > 0)
			{
				$doc = new stdclass;
				$doc->status = 400;
				$doc->message = "POST body missing value(s) for: " . join(", ", $required_parameters);
				send_doc($doc);				
			}
		
			$doc->status = 200;
		}
	}
	
	return $doc;
}

//----------------------------------------------------------------------------------------
function send_doc($doc, $callback = '')
{
	switch ($doc->status)
	{
		case 303:
			header('HTTP/1.1 303 See Other');
			break;
			
		case 400:
			header('HTTP/1.1 400 Bad request');
			break;

		case 404:
			header('HTTP/1.1 404 Not Found');
			break;
		
		case 410:
			header('HTTP/1.1 410 Gone');
			break;
		
		case 500:
			header('HTTP/1.1 500 Internal Server Error');
			break;
				
		case 200:
		default:
			header('HTTP/1.1 200 OK');
			break;
	}
	
	//header("Content-type: text/plain");
	header("Content-type: application/json; charset=utf-8");
	
	if ($callback != '')
	{
		echo $callback . '(';
	}
	echo json_encode($doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if ($callback != '')
	{
		echo ')';
	}			
	
	exit();

}


?>
