<?php

// TSV to web page
$filename = 'output.tsv';
?>

<html>
<head>
	<style>
	body {
		font-family:sans-serif;
		margin:0px;
		padding:0px;
	}
	tbody {
		font-size:0.8em;
	}
	td {
		vertical-align: top;
		border-bottom:1px solid rgb(192,192,192);
		margin:0px;
		padding:4px;
	}
	</style>
</head>
<body>

<div style="position:relative;width:100%">

<div style="float:left;width:90%;height:100%;overflow-y:auto;border:1px solid rgb(192,192,192);padding:1em;">

<table cellpadding="0" cellspacing="0">

<?php

// output

$output_keys = array('id', 'scientificname', 'citation', 'parsed', 'bhl', 'title', 'doi', 'matched',
'prefix', 'text', 'suffix');

echo '<tr>';
foreach ($output_keys as $key)
{
	echo '<th>';
	echo $key;
	echo '</th>';
}
echo '</tr>';

// input
$headings = array();
$row_count = 0;
$file = @fopen($filename, "r") or die("couldn't open $filename");
		
$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$row = fgetcsv(
		$file_handle, 
		0, 
		"\t" 
		);		
	$go = is_array($row);
	
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
		
			//print_r($obj);	
			
			$output_row = array();
			foreach ($output_keys as $key)
			{
				if (isset($obj->{$key}))
				{
					$output_row[] = $obj->{$key};
				}
				else
				{
					$output_row[] = "";
				}
			}
			echo '<tr>';
			echo '<td>';
			echo join("</td><td>", $output_row);
			echo '</td>';
			echo '</tr>' . "\n";

		}
	}	
	$row_count++;

}

?>

</table>

</div>
</div>
</body>
</html>
