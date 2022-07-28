<?php

error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

$config = array();

$config['database'] = 'sqlite:' . dirname(__FILE__) . '/database/matching.db';

$config['cache'] = dirname(__FILE__) . '/cache'; 

// Environment----------------------------------------------------------------------------
// In development this is a PHP file that is in .gitignore, when deployed these parameters
// will be set on the server
if (file_exists(dirname(__FILE__) . '/env.php'))
{
	include 'env.php';
}

$config['BHL_API_KEY'] = getenv('BHL_API_KEY');

?>
