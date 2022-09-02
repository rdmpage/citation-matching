<?php

// read JSONL and export simple TSV
$filename = "sheets/muelleria.json";
$filename = "mulleria.json";
$filename = "exotic.json";


$table_name = "reference";
$reference_key = 'id';

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
		
		/*		
		if ($doi == '')
		{
			$ok = false;
		}
		*/	
			
		
		//print_r($obj);
		
		// matched to BHL page?
		if (isset($obj->bhl))
		{
			// matched to name?
			if (isset($obj->selector))
			{
				$bhl_score = 100;
				foreach ($obj->selector as $selector)
				{
					$bhl_score = min($bhl_score, $selector->score);
				}

				$pairs = array();
			
				$pairs[] = 'bhl=' . $obj->bhl;
				$pairs[] = 'bhl_score=' . $bhl_score;
			
				echo 'UPDATE ' . $table_name . ' SET ' . join(",", $pairs) . ' WHERE ' . $reference_key . '="' . $obj->id . '";' . "\n";

			}
		}
	}
}	
