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
		if (preg_match("/{storage: {$name}}:/", $database)) {
			throw new \Koala\Exceptions\StorageAlreadyExistsException("Storage {$name} already exists in database {$databaseName}.");
		}

		// Create storage columns
		foreach ($columns as $key => $column) {
			$storageColumns .= (($key == count($columns) - 1) ? "{{$column}: null}" : "{{$column}: null},");
		}

		// Create storage
		$storage = "[{storage: {$name}}:[{$storageColumns}]]\n";

		// Write the storage in the database file
		$database .= $storage;
		file_put_contents($databaseFile, $database);
	}

	public function store(array $storage) {
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
		if (!preg_match("/{storage: {$storage}}:/", $database)) {
			throw new \Koala\Exceptions\StorageNotFoundException("Storage {$storage} was not found in {$databaseName}.");
		}

	}

}
