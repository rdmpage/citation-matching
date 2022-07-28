<?php

// testing GET

require_once (dirname(__FILE__) . '/api_utilities.php');

$doc = http_get_endpoint(["q"]);
send_doc($doc);	

?>
