<?php

namespace Koala;

class Koala {

	public function __construct() {
	}

	public function newDatabase($name) {
		// Check if database exists
		if (file_exists("protected/{$name}.koala")) {
			throw new \Koala\Exceptions\DatabaseAlreadyExistsException("Database {$name} already exists.");
		}

		// Create the database file
		$database = fopen("protected/{$name}.koala", 'w');
		fwrite($database, '# ' . $name . "\n");
	}

	public function newStorage($name, $databaseName, array $columns) {
		$storageColumns = null;

		// Check if database exists
		if (!file_exists("protected/{$databaseName}.koala")) {
			throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$databaseName} was not found.");
		}

		// Open the database file
		$databaseFile = "protected/{$databaseName}.koala";
		$database = file_get_contents($databaseFile);

		// Check if storage exists in database
		if (preg_match("/{storage:{$name}}/", $database)) {
			throw new \Koala\Exceptions\StorageAlreadyExistsException("Storage {$name} already exists in database {$databaseName}.");
		}

		// Create storage columns
		foreach ($columns as $key => $column) {
			$storageColumns .= (($key == count($columns) - 1) ? "{$column}" : "{$column},");
		}

		// Create storage
		$storage = "storage:{$name},columns:[{$storageColumns}],data:[[%s]]\n";

		// Write the storage in the database file
		$database .= $storage;
		file_put_contents($databaseFile, $database);
	}

	public function store(array $storage, array $data) {
		$databaseName = key($storage);
		$storage = $storage[$databaseName];

		// Check if specified database exists
		if (!file_exists("protected/{$databaseName}.koala")) {
			throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$databaseName} was not found.");
		}

		// Open the database file
		$databaseFile = "protected/{$databaseName}.koala";
		$database = file_get_contents($databaseFile);

		// Check if specified storage exists in database
		if (!preg_match("/storage:{$storage}/", $database)) {
			throw new \Koala\Exceptions\StorageNotFoundException("Storage {$storage} was not found in {$databaseName}.");
		}

		// Select the specified storage
		$database = file($databaseFile);

		foreach ($database as $line => $db) {
			if (preg_match("/storage:{$storage}/", $db)) {
				$storageLine = $line;
			}
		}

		// Get storage's columns
		preg_match_all('/columns:\[[a-z,]+\]/', $database[$storageLine], $matchedColumns);
		preg_match_all('/\[([^\)]*)\]/', $matchedColumns[0][0], $matchedColumns);
		$matchedColumns = explode(',', $matchedColumns[1][0]);

		$dataToStore = null;
		$storedData = null;
		$key = 0;

		// If the specified column doesn't exist in storage's column
		// then we set it to null and store it.
		foreach ($data as $column => $value) {
			if (!in_array($column, $matchedColumns)) {
				$key++;
				$dataToStore[$matchedColumns[$key]] = 'null';
			} else {
				$dataToStore[$column] = $value;
			}
		}

		foreach ($dataToStore as $column => $value) {
			$storedData .= "{{$column}:{$value}},";
		}
		
		// Update the file
		$matchedColumns = rtrim(implode(',', $matchedColumns), ',');
		$storedData = rtrim($storedData, ',');

		$database[$storageLine] = sprintf($database[$storageLine], $storedData);
		$database = implode('', $database);
		$database = rtrim($database, "]\n");
		$database .= "],[%s]]\n";
		file_put_contents($databaseFile, $database);
	}

}
