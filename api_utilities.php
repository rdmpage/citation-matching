<?php

error_reporting(E_ALL);

//----------------------------------------------------------------------------------------
function get($url, $format = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	
	if ($format != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: " . $format));	
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
function post($url, $data = '', $content_type = '')
{
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	if ($content_type != '')
	{
		curl_setopt($ch, CURLOPT_HTTPHEADER, 
			array(
				"Content-type: " . $content_type
				)
			);
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
			$doc->message = json_last_error_msg();
	
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
	header("Content-type: application/json");
	
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
