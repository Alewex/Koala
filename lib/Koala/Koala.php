<?php

namespace Koala;

class Koala {

	public function __construct() {
	}

	public function databaseExists($name) {
		return (file_exists("protected/{$name}.koala")) ? true :  false;
	}

	public function newDatabase($name, $encrypted = false) {
		// Check if database exists
		if ($this->databaseExists($name)) throw new \Koala\Exceptions\DatabaseNotFoundException("Database {$name} was not found.");

		// Create the database file
		$database = fopen("protected/{$name}.koala", 'w');
		fwrite($database, '# ' . $name . " | Koala\n");
		fclose($database);

		// Encrypt database
		if ($encrypted) $this->encrypt($name);
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
		preg_match_all('/columns:\[[a-z,]+\]/', $database[$storageLine], $storageColumns);
		preg_match_all('/\[([^\)]*)\]/', $storageColumns[0][0], $storageColumns);
		$storageColumns = explode(',', $storageColumns[1][0]);

		$dataToStore = null;
		$storedData = null;
		$key = 0;

		// If the specified column doesn't exist in storage's column
		// then we set it to null and store it.
		foreach ($data as $column => $value) {
			if (!in_array($column, $storageColumns)) {
				$key++;
				if (count($storageColumns) > $key) {
					$dataToStore[$storageColumns[$key]] = 'null';
				}
			} else {
				$dataToStore[$column] = $value;
			}
		}

		foreach ($dataToStore as $column => $value) {
			$storedData .= "{{$column}:{$value}},";
		}
		
		// Update the file
		$storageColumns = rtrim(implode(',', $storageColumns), ',');
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

	public function retrieve(array $storage, $columns = true, $match = true) {
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

		// Get storage's columns and check
		preg_match_all('/columns:\[[a-zA-Z,]+\]/', $database[$storageLine], $storageColumns);
		preg_match_all('/\[([^\)]*)\]/', $storageColumns[0][0], $storageColumns);
		$storageColumns = explode(',', $storageColumns[1][0]);

		// See if the user selected all the columns
		if (!is_array($columns) && $columns == true) {
			$selectedColumns = $storageColumns;
		} else {
			$selectedColumns = array_intersect($storageColumns, $columns);
		}

		// See if selected columns exists in storage
		if (!count($selectedColumns) > 0) {
			return null;
		}

		// Get storage data
		preg_match_all('/data:\[(.*?)\]\]/', $database[$storageLine], $storageData);
		preg_match_all('/\[([^\)]*)\]/', $storageData[0][0], $storageData);
		$storageData = explode('],', $storageData[1][0]);

		unset($storageData[count($storageData)-1]);

		// Check if data in the storage matches the request
		// todo: clean this
		if (!is_array($match)) {
			$dataObj = [];
			foreach ($storageData as $data) {
				$data = trim($data, '[');
				$data = explode(',', $data);
				$data = str_replace(['{', '}'], '', $data);

				foreach ($data as $data) {
					$result = explode(':', $data);
					$d[$result[0]] = $result[1];
				}
				$dataObj[] = $d;
			}
			// Encrypt file if it was already encrypted
			if ($isEncrypted) $this->encrypt($databaseName);

			return $dataObj;
		} else {
			foreach ($match as $column => $value) {
				$where[] = "{{$column}:{$value}}";
			}
		}

		if (preg_grep("/{$where[0]}/", $storageData)) {
			$matchedResult = preg_grep("/{$where[0]}/", $storageData);
			$matchedResult = trim(array_shift($matchedResult), '[');
		} else {
			return null;
		}

		// Convert data string into array
		$matchedResult = explode(',', str_replace(['{', '}'], '', $matchedResult));

		foreach ($matchedResult as $result) {
			$result = explode(':', $result);
			$data[$result[0]] = $result[1];
		}

		// Intersect requested columns
		$selectedColumns = array_flip($selectedColumns);
		$data = array_intersect_key($data, $selectedColumns);

		// Encrypt file if it was already encrypted
		if ($isEncrypted) $this->encrypt($databaseName);

		return $data;
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

		if (base64_decode($database, true) == true) {
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
