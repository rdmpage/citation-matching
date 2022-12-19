<?php

require_once (dirname(__FILE__) . '/workflow.php');

// create workflow

// Parse string
$node1 = new Node('http://localhost/citation-matching/api/fullparser.php');

// Look for DOI
$node2 = new Node('http://localhost/citation-matching/api/crossref_openurl.php');

$node1->SetNext($node2);


$headings = array();

$row_count = 0;

$filename = "reftest.csv";

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$line = trim(fgets($file_handle));
		
	$row = explode("\t",$line);
	
	$go = is_array($row) && count($row) > 1;
	
	if ($go)
	{
		if ($row_count == 0)
		{
			$headings = $row;		
		}
		else
		{
			$obj = new stdclass;
		
			foreach ($row as $k => $v)
			{
				if ($v != '')
				{
					$obj->{$headings[$k]} = $v;
				}
			}
			
			$doc = new stdclass;
			$doc->q = $obj->citation;
			
			// start with node1
			$next = $node1;

			while ($next)
			{
				$next = $next->Run($doc);
			}

			print_r($doc);			
		}
	}	
	$row_count++;	
	
}	




?>
