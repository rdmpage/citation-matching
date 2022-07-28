<?php

// Create a SQLite database from a sets of titles and ISSNs

require_once(dirname(__FILE__) . '/sqlite.php');

$pdo     = new PDO($config['database']); 	// name of SQLite database (a file on disk)
$basedir = dirname(__FILE__) . '/issn';

$files = scandir($basedir);

foreach ($files as $filename)
{
	if (preg_match('/\.tsv$/', $filename))
	{	
		import_csv_to_sqlite($pdo, $basedir . '/' . $filename, $options = array('delimiter' => "\t", 'table' => 'issn'));
	}
}

?>
