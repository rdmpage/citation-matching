<?php

// SQLite functions

require_once (dirname(dirname(__FILE__)) . '/config.inc.php');


//----------------------------------------------------------------------------------------
// https://gist.github.com/fcingolani/5364532
function import_csv_to_sqlite(&$pdo, $csv_path, $options = array())
{
    extract($options);

    if (($csv_handle = fopen($csv_path, "r")) === FALSE)
        throw new Exception('Cannot open CSV file');

    if(!isset($delimiter))
    {
        $delimiter = ',';
    }

    if(!isset($table))
    {
        $table = preg_replace("/[^A-Z0-9]/i", '', basename($csv_path, '.csv'));
	}
 
    if(!isset($fields)){
        $fields = array_map(function ($field){
            return strtolower(preg_replace("/[^A-Z0-9]/i", '', $field));
        }, fgetcsv($csv_handle, 0, $delimiter));
    }

    $create_fields_str = join(', ', array_map(function ($field){
        return "`$field` TEXT NULL";
    }, $fields));

    $pdo->beginTransaction();

    $create_table_sql = "CREATE TABLE IF NOT EXISTS $table ($create_fields_str)";
        
    $pdo->exec($create_table_sql);

	$insert_fields_str = join(', ', array_map(function ($field){
        return "`$field`";
    }, $fields));    
    
    $insert_values_str = join(', ', array_fill(0, count($fields),  '?'));
    $insert_sql = "INSERT INTO $table ($insert_fields_str) VALUES ($insert_values_str)";
    $insert_sth = $pdo->prepare($insert_sql);

    $inserted_rows = 0;
    while (($data = fgetcsv($csv_handle, 0, $delimiter)) !== FALSE) {
        $insert_sth->execute($data);
        $inserted_rows++;
    }

    $pdo->commit();

    fclose($csv_handle);

    return array(
            'table' => $table,
            'fields' => $fields,
            'insert' => $insert_sth,
            'inserted_rows' => $inserted_rows
        );

}

//----------------------------------------------------------------------------------------
function do_query($sql)
{
	global $config;
		
	$pdo = new PDO($config['database']);
	
	$stmt = $pdo->query($sql);

	$data = array();

	while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {

		$item = new stdclass;
		
		$keys = array_keys($row);
	
		foreach ($keys as $k)
		{
			if ($row[$k] != '')
			{
				$item->{$k} = $row[$k];
			}
		}
	
		$data[] = $item;
	}
	
	return $data;	
}


?>
