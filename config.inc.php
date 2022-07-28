<?php

error_reporting(E_ALL);
mb_internal_encoding('UTF-8');

$config = array();

$config['database'] = 'sqlite:' . dirname(__FILE__) . '/database/matching.db';

?>
