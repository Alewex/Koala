<?php

namespace Koala;

class Koala {

	public function __construct() {
	}

	public function databaseExists($name) {
		return (file_exists("protected/{$name}.koala")) ? true :  false;
	}

	public function newDatabase($name) {
		// Check if database exists
		if ($this->databaseExists($name)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$name} was not found.");

		// Create the database file
		$database = fopen("protected/{$name}.koala", 'w');
		fwrite($database, '# ' . $name . " | Koala\n");
	}

	public function newStorage($name, $databaseName, array $columns) {
		$storageColumns = null;
		$isEncrypted = false;

		// Check if database exists
		if (!$this->databaseExists($databaseName)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$databaseName} was not found.");

		// Check if database is encrypted
		if ($this->isEncrypted($databaseName)) {
			$isEncrypted = true;
			$this->decrypt($databaseName);
		}

		// Open the database file
		$databaseFile = "protected/{$databaseName}.koala";
		$database = file_get_contents($databaseFile);

		// Check if storage exists in database
		if (preg_match("/storage:{$name}/", $database)) {
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

		// Encrypt file if it was already encrypted
		if ($isEncrypted) $this->encrypt($databaseName);
	}

	public function store(array $storage, array $data) {
		$databaseName = key($storage);
		$storage = $storage[$databaseName];
		$isEncrypted = false;

		// Check if specified database exists
		if (!$this->databaseExists($databaseName)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$databaseName} was not found.");

		// Check if specified database is encrypted
		if ($this->isEncrypted($databaseName)) {
			$isEncrypted = true;
			$this->decrypt($databaseName);
		}

		// Open the database file
		$databaseFile = "protected/{$databaseName}.koala";
		$database = file_get_contents($databaseFile);

		// Check if specified storage exists in database
		if (!preg_match("/storage:{$storage}/", $database)) {
			throw new \Koala\Exceptions\StorageNotFoundException("Storage {$storage} was not found in {$databaseName}.");
		}

		$database = file($databaseFile);

		// Select the specified storage
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
				if (count($matchedColumns) > $key) {
					$dataToStore[$matchedColumns[$key]] = 'null';
				}
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
		$database[$storageLine] = rtrim($database[$storageLine], "\n");
		$database[$storageLine] = substr($database[$storageLine], 0, -2);
		$database[$storageLine] .= "],[%s]]\n";
		$database = implode('', $database);
		file_put_contents($databaseFile, $database);

		// Encrypt file if it was already encrypted
		if ($isEncrypted) $this->encrypt($databaseName);
	}

	public function encrypt($database) {
		$file = "protected/{$database}.koala";

		// Check if specified database exists
		if (!$this->databaseExists($database)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$database} was not found.");

		// Encode file
		$database = file_get_contents($file);
		$database = base64_encode($database);
		file_put_contents($file, $database);
	}

	public function isEncrypted($database) {
		$file = "protected/{$database}.koala";

		// Check if specified database exists
		if (!$this->databaseExists($database)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$database} was not found.");

		$database = file_get_contents($file);

		if (base64_decode($database)) {
			base64_encode($database);
			return true;
		} else {
			return false;
		}
	}

	public function decrypt($database) {
		$file = "protected/{$database}.koala";

		// Check if specified database exists
		if (!$this->databaseExists($database)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$database} was not found.");

		// Encode file
		$database = file_get_contents($file);
		$database = base64_decode($database);
		file_put_contents($file, $database);
	}

}
