<?php

// read JSONL

?>
<html>
<head>
	<style>
/*
Tiny Sweet Blue: #b5e9e9

Creamy Light Tan: #fef6dd

Pinkie Pie: #ffe1d0

Yellow Horse: #fff1b5

Green Thumb: #dcf3d0
*/	
	
	
	body {
		font-family:sans-serif;
		padding:2em;
		background: white;
	}
	span {
		padding-left:1em;
		padding-right:1em;
		display:inline-block;
	}
	
	.row {
	  display: flex;
	  /* border: 1px solid black; */
	}

	.column {
	  flex: 40%;
	  padding:1em;
  
	}	
	
	/*https://www.color-hex.com/color-palette/35021 */
	
	.green {
		background:#2dc937;
		/*color:rgb(252, 241, 144);*/
	}
	
	.yellow {
		background:#e7b416;
	}
	
	
	.orange {
		background:#db7b2b;
	}

	.red {
		background:#cc3232;
		/*color:rgb(252, 241, 144);*/
	}
	
	
	.id {
		width: 30px;
		text-align:right;
		/* border:1px solid black; */
	}
	
	.name {
		min-width: 200px;
	}
	
	summary {
		line-height: 2em;
		font-size: 0.8em;
			
		
	}
	
	details {
	/*background:#EBEBEB;*/
	box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
	
	}
	
	.more {
		padding:1em;
	}
	
	pre {
		background:white;
		font-size:0.8em;
		padding:2em;
		/* border:1px solid #94f0f1; */
		box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);
	}
	
	img {
			box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);

	}
	</style>
</head>
<body>

<h1>Matching microcitations to DOIs, pages, and text positions</h1>
<p></p>

<h2>Guide</h2>


<div><span class="red">red</span> Failed to parse the citation</div>
<div><span class="orange">orange</span> Parsed the citation but can't match to a page</div>
<div><span class="yellow">yellow</span> Matched to a page (and possibly work) but no string match</div>
<div><span class="green">green</span> Found a string matching the name on the page</div>

<h2>Results</h2>

<?php

$filename = "muelleria.json";
$filename = "telopea.json";
$filename = "m.json";
$filename = "nota.json";
//$filename = "exotic.json";


$counter = 1;

$file_handle = fopen($filename, "r");
while (!feof($file_handle)) 
{
	$json = trim(fgets($file_handle));
	
	$obj = json_decode($json);

	//print_r($obj);
	
	if ($obj)
	{
	
		$class = "red";
		
		if ($obj->parsed)
		{
			$class = "orange";
		}
		
		if (isset($obj->bhl))
		{
			$class = "yellow";
		}		

		if (isset($obj->matched))
		{
			$class = "green";
		}
	
		echo '<details>';
		echo '	<summary class="' . $class . '">';
	
		echo '<span class="id">' . $obj->id . '</span>' . "\n";
		echo '<span class="name">' . $obj->scientificname . '</span>' . "\n";
				
		echo '<span>' . $obj->citation . '</span>' . "\n";
		echo '	</summary>';
		
		echo '<div class="more">';
		
		if (isset($obj->bhl))
		{
			$pages = explode(".", $obj->bhl);
			
			echo '<div>Citation matches BHL page(s):';
			
			foreach ($pages as $page)
			{
				echo ' <a href="https://www.biodiversitylibrary.org/page/' . $page . '" target="_new">' . $page . '</a>';
			}
			
			echo '</div>';
		}
		
		if (isset($obj->parts))
		{
			foreach ($obj->parts as $work)
			{
				echo '<div>Page is part of the work <b>' . $work->title . '</b>';
		
				if (isset($work->doi))
				{
					echo ', <a href="https://doi.org/' . $work->doi . '" target="_new">doi:' . $work->doi . '</a>';
				}
			
				echo '</div>';
			}
		}
		
		if (!$obj->parsed)
		{
			echo '<p>Could not parse the citation "' . addcslashes($obj->citation, '"') . '".</p>';
		}
		
	
		if (isset($obj->matched))
		{
echo '<div class="row">';
	
echo '	<div class="column" style="color:#5E5E5E;">';
echo ' <h3>Page text</h3>';

$html = htmlentities($obj->html);

$html = str_replace('&lt;pre&gt;', '<pre>', $html);
$html = str_replace('&lt;/pre&gt;', '</pre>', $html);
$html = str_replace('&lt;mark&gt;', '<mark>', $html);
$html = str_replace('&lt;/mark&gt;', '</mark>', $html);

echo 	$html;
echo '	</div>';

echo '	<div class="column">';
echo ' <h3>Page image</h3>';
if (1)
{
	echo '		<img width="100%" src="https://aipbvczbup.cloudimg.io/s/height/700/https://www.biodiversitylibrary.org/pageimage/' . $obj->matched . '">';
}
else
{
	echo '		<img width="100%" src="https://aipbvczbup.cloudimg.io/s/height/700/' . $obj->image . '">';
}
echo '	</div>';


echo '</div>	';
	
	
		}

		echo '</div>';

		echo '</details>';
	}	
	
	/*
	if ($counter++ > 200)
	{
		break;
	}
	*/
}	

?>

</body>
</html>
