<?php

require_once (dirname(__FILE__) . '/api_utilities.php');

// testing POST

$doc = http_post_endpoint();
send_doc($doc);	

?>
