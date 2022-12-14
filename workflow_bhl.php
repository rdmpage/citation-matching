<?php

require_once (dirname(__FILE__) . '/workflow.php');

// create workflow

// Parse string
$node1 = new Node('http://localhost/citation-matching/api/parser.php');

// Match citation to BHL using database
$node2 = new Node('http://localhost/citation-matching/api/bhl_db.php');
$node2 = new Node('http://localhost/citation-matching/api/bhl.php');

$node1->SetNext($node2);

// Find name in text
$node3 = new Node('http://localhost/citation-matching/api/text_doc.php');
$node2->SetNext($node3);

// Initial doc is target name and text citation
$doc = new stdclass;
$doc->q = "Exot. Microlepid. 1: 278.";
$doc->name = "Brachmia torva";

$doc = new stdclass;
$doc->q = "Ann. Transv. Mus. 8: 68.";
$doc->name = "Telphusa accensa";

$doc = new stdclass;
$doc->q = "Trans. ent. Soc. Lond. 1917: 40.";
$doc->name = "Tholerostola";

$doc = new stdclass;
$doc->q = "Proc. U.S. natn. Mus. 33: 197.";
$doc->name = "Phthorimaea laudatella";

$doc = new stdclass;
$doc->q = "Mitt. münch. ent. Ges. 57: 117.";
$doc->name = "Anacampsi";

$doc = new stdclass;
$doc->q = "Tijdschr. Ent. 84: 352, text-figs 1, 3, pl. 1, figs 1-4.";
$doc->name = "Anacampsis betulinella";

$doc = new stdclass;
$doc->q = "Tijdschr. Ent., 29, 258.";
$doc->name = "Abascantus";



$doc = new stdclass;
$doc->q = "Boll. Mus. Torino, 18, no. 433, 5.";
$doc->name = "Abatodesmus";


$doc = new stdclass;
$doc->q = "Bull. Soc. imp. Nat. Moscou 57(1): 27.";
$doc->name = "Tachyptilia solemnella";

$doc = new stdclass;
$doc->q = "Novit. zool., 32: 52.";
$doc->name = "Fascellina meligerys";




// start with node1
$next = $node1;

while ($next)
{
	print_r($doc);
	$next = $next->Run($doc);
}

print_r($doc);


?>
