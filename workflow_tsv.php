<?php

// Read rows from a TSV file and call a workflow

// This requires us to read multiple rows and start workflow for each one.

require_once (dirname(__FILE__) . '/workflow.php');


$node1 = new FileNode('reftest.csv');
$node3 = new DumpNode();
$node2 = new Node('http://localhost/citation-matching/api/fullparser.php');
$node4 = new Node('http://localhost/citation-matching/api/crossref_openurl.php');

// full parse and DOI lookup
$node1->SetNext($node2);
$node2->SetNext($node4);
$node4->SetNext($node3);
$node3->SetNext(null);

//----------------------------------------------------------------------------------------
class AnnotationNode extends Node
{

	function __construct()
	{

	}
	
	function Run(&$doc)
	{
		//print_r($doc);
		
		if (isset($doc->hits))
		{
			foreach ($doc->hits as $page_id => $hit)
			{
				if ($hit->total != 0)
				{
					foreach ($hit->selector as $selector)
					{
						$keys = array();
						$values = array();
						
						// source database id for this record
						$keys[] = 'id';
						$values[] = '"' . $doc->id . '"';						
									
						// identifier for this page			
						$keys[] = 'pageid';
						$values[] = '"' . $page_id . '"';						

						// page number
						if (isset($doc->page))
						{
							$keys[] = 'page';
							$values[] = '"' . $doc->page . '"';						
						}
						
						// page text
						if (isset($doc->text->$page_id))
						{
							$keys[] = 'text';
							$values[] = '"' . str_replace('"', '""', $doc->text->$page_id) . '"';							
						}

						// annotation
						$keys[] = 'body';
						$values[] = '"' . str_replace('"', '""', $selector->body) . '"';						
				
						$keys[] = 'exact';
						$values[] = '"' . str_replace('"', '""', $selector->exact) . '"';						

						$keys[] = 'prefix';
						$values[] = '"' . str_replace('"', '""', $selector->prefix) . '"';						

						$keys[] = 'suffix';
						$values[] = '"' . str_replace('"', '""', $selector->suffix) . '"';						

						$keys[] = 'start';
						$values[] = $selector->range[0];						

						$keys[] = 'end';
						$values[] = $selector->range[1];	
									
						$keys[] = 'score';
						$values[] = $selector->score;	
						
						// create a "guid" for the annotation
						$keys[] = 'guid';
						$values[] = '"' . md5(join('', $values)) . '"';						
						
						// print_r($keys);
						// print_r($values);
						
						$sql = 'REPLACE INTO annotation (' . join(',', $keys) . ') VALUES (' . join(',', $values) . ');';
						
						echo $sql . "\n";
					}
				}
			}		
		}
	
		
		// 
		
			
		if ($this->next)
		{
			return $this->next;
		}
		else
		{
			return null;
		}
	}

}


//----------------------------------------------------------------------------------------
function flow_parse()
{
	$node1 = new Node('http://localhost/citation-matching/api/fullparser.php');
	$node2 = new DumpNode();
	
	$node1->SetNext($node2);
	
	return $node1;
}

//----------------------------------------------------------------------------------------
function flow_doi()
{
	$node1 = new Node('http://localhost/citation-matching/api/fullparser.php');
	$node2 = new Node('http://localhost/citation-matching/api/crossref_openurl.php');
	$node3 = new DumpNode();
	
	$node1->SetNext($node2);
	$node2->SetNext($node3);
	
	return $node1;
}

//----------------------------------------------------------------------------------------
function flow_reconcile()
{
	$node1 = new Node('http://localhost/citation-matching/api/reconcile.php');
	$node2 = new DumpNode();
	
	$node1->SetNext($node2);
	
	return $node1;
}

//----------------------------------------------------------------------------------------
function flow_bhl()
{
	$node1 = new Node('http://localhost/citation-matching/api/parser.php');
	$node2 = new Node('http://localhost/citation-matching/api/bhl.php');
	$node3 = new Node('http://localhost/citation-matching/api/text_doc.php');

	$node4 = new AnnotationNode();

	$node1->SetNext($node2);
	$node2->SetNext($node3);
	$node3->SetNext($node4);
	
	return $node1;
}


//----------------------------------------------------------------------------------------

$filename = 'reftest.csv';
$filename = 'nomen.tsv';


// create workflow to process one line pooped off the TSV file
$doc = null;

$start = flow_parse();
$start = flow_doi();
//$start = flow_reconcile();
$start = flow_bhl();

// Create a service that extracts one line at a time from the TSV file
$flow_tsv = new FileNode($filename);
$flow_tsv->SetNext($start);

do
{
	// get a row of data, which is stored in doc
	$row = $flow_tsv->Run($doc);
	
	if ($row)
	{
		// do any data transformation we need here		
		if (isset($doc->citation))
		{
			$doc->q = $doc->citation;
		}

		if (isset($doc->{'col:citation'}))
		{
			$doc->q = $doc->{'col:citation'};
		}
		
		// call workflow
		$next = $flow_tsv->GetNext();
		while ($next)
		{
			$next = $next->Run($doc);
		}
	}

} while ($row);


?>
