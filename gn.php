<?php

$filename = 'cache/33707754.json';
//$filename = 'cache/3218347.json';
$filename = 'cache/15626267.json';
$filename = 'cache/34803669.json';


$json = file_get_contents($filename);

$obj = json_decode($json);

//print_r($obj);

$text = $obj->Result->OcrText;

//echo $text;

$text_filename = 'test.txt';

file_put_contents($text_filename, $text);

$command = 'gnfinder --utf8-input --words-around 4 --format pretty ' . $text_filename;

$json = shell_exec($command);

echo $json;

$response = json_decode($json);

$tag_starts = array();
$tag_ends = array();

foreach ($response->names as $name)
{
	if (!isset($tag_starts[$name->start]))
	{
		$tag_starts[$name->start] = array();
	}
	$tag_starts[$name->start][] = 'mark';
	
	if (!isset($tag_ends[$name->end]))
	{
		$tag_ends[$name->end] = array();
	}
	
	$tag_ends[$name->end][] = 'mark';
}

// split origjnal text into array of characters
$text_array = mb_str_split($text);

$html = '<pre>';

foreach ($text_array as $pos => $char)
{
	// echo $pos . ' ' . $char . "\n";

	if (isset($tag_ends[$pos]))
	{
		foreach ($tag_ends[$pos] as $tag)
		{
			$html .= '</' . $tag . '>';
		}
	}


	if (isset($tag_starts[$pos]))
	{		
		foreach ($tag_starts[$pos] as $tag)
		{
			$html .=  '<' . $tag . '>';
		}
	}

	$html .=  $char;

}

$html .= '</pre>';

echo $html;



?>
