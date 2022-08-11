<?php

// read JSONL and export simple TSV
$filename = "sheets/muelleria.json";

$counter = 1;

$headings = array(
'id',	
'instanceId', /* not set */
'taxonId', /* not set */
'scientificname',
'rank', /* not set */
'doi',
'matched',
'citation',
);

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$json = trim(fgets($file_handle));
	
	$obj = json_decode($json);
	
	if ($obj)
	{
		//print_r($obj);
	
		$ok = true;
		
		if (!$obj->parsed)
		{
			$ok = false;
		}
		
		if (!isset($obj->matched))
		{
			$ok = false;
		}
				
		$doi = '';
		if (isset($obj->parts))
		{
			foreach ($obj->parts as $work)
			{
				if (isset($work->doi))
				{
					$doi = $work->doi;
				}
			}
		}		
		if ($doi == '')
		{
			$ok = false;
		}	
			
		
		//print_r($obj);
		
		/*
		if ($ok)
		{
			echo "OK\n";
		}
		else
		{
			echo "Badness\n";
		}
		*/
		
		if ($ok)
		{
			$output_row = array();
			
			foreach ($headings as $key)
			{
				switch ($key)
				{
					case 'doi':
						$output_row[] = $doi;
						break;
						
					default:
						if (isset($obj->{$key}))
						{
							$output_row[] = $obj->{$key};
						}
						else
						{
							$output_row[] = '';
						}
						break;
				}
			}
			
			echo join("\t", $output_row) . "\n";
			
			if ($counter++ == 10)
			{
				break;
			}
		}
	}
}	
