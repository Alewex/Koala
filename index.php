<?php

// Require Composer's autoload file
require_once 'vendor/autoload.php';

// Start Koala
$k = new Koala\Koala;

//////////////////////////////////////////////////////

$userId1 = $k->retrieve(['EncryptedDB' => 'Admin'], [
	'name'
]);

var_dump($userId1);

//////////////////////////////////////////////////////

/**
 *
 * Create a database.
 * Each database is an individual .koala file
 * which can contain storages.
 *
 */
// $k->newDatabase('KoalaDB');

/**
 *
 * Create a storage.
 * You can see storages as tables in SQL.
 *
 */
// $k->newStorage('Users', 'KoalaDB', [
// 	'id',
// 	'name',
// 	'age',
// 	'location'
// ]);
// $k->newStorage('Admin', 'KoalaDB', [
// 	'id',
// 	'name',
// 	'password'
// ]);

/**
 *
 * Storing data on storages.
 *
 */
// $k->store(['KoalaDB' => 'Users'], [
// 	'name' => 'Alex',
// 	'agse' => 17,
// 	'asd' => 'UK',
// 	'das' => 'UK',
// 	'asd' => 'UK',
// 	'da' => 'UK',
// 	'location' => 'UK',
// 	'sdddd' => 'UK',
// 	'price' => 39299
// ]);

/**
 *
 * Encrypt or decrypt a database.
 *
 */
// $k->decrypt('EncryptedDB');