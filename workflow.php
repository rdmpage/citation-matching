<?php

// A node in a simple n8n like workflow, with some specialised nodes that extend the
// base node.

require_once (dirname(__FILE__) . '/api/api_utilities.php');

//----------------------------------------------------------------------------------------
class Node
{
	var $url  = ''; // API call
	var $next = null; // Next node in workflow

	function __construct($path)
	{
		$this->url = $path;
	}
	
	function GetNext()
	{
		return ($this->next);
	}	
	
	function SetNext($NodePtr)
	{
		$this->next = $NodePtr;
	}
	
	function Run(&$doc)
	{
		$next = null;		
		$json = post($this->url, $doc);
		$doc = json_decode($json);		
		if ($doc)
		{
			if ($doc->status == 200)
			{
				if ($this->next)
				{
					// still more nodes in the workflow
					$next = $this->next;
				}
				else
				{
					// end
				}
			}
			
		}
		else
		{
			// badness happened
		}
	
		return $next;	
	}

}

// Just dump data for display
//----------------------------------------------------------------------------------------
class DumpNode extends Node
{

	function __construct()
	{

	}
	
	function Run(&$doc)
	{
		print_r($doc);
			
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
// Parse a row in a TSV file and call next node in the workflow
class FileNode extends Node
{
	var $file_handle = null;
	var $headings = array();
	
	function __construct($path)
	{
		$this->file_handle = fopen($path, "r");
		$this->row_count = 0;
		$this->headings = array();
		
		// get headings
		$line = trim(fgets($this->file_handle));
		$this->headings = explode("\t", $line);
	}
	
	// Read a row of data, if OK return pointer to next item in flow
	function Run(&$doc)
	{
		$next = null;
		if (feof($this->file_handle))
		{
		}
		else
		{
			$line = trim(fgets($this->file_handle));		
			$row = explode("\t",$line);
			
			if (is_array($row) && count($row) > 1)
			{
				$doc = new stdclass;		
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$doc->{$this->headings[$k]} = $v;
					}
				}
				
				if (isset($this->next))
				{
					$next = $this->next;
				}
			}
		}
		return $next;
	}

}


?>
