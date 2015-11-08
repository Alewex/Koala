<?php

// Require Composer's autoload file
require_once 'vendor/autoload.php';

// Start Koala
$k = new Koala\Koala;

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
// 	'name',
// 	'age',
// 	'location'
// ]);
// $k->newStorage('Products', 'KoalaDB', [
// 	'name',
// 	'description',
// 	'price'
// ]);
// $k->newStorage('Test', 'KoalaDB', [
// 	'id'
// ]);
/**
 *
 * Storing data on storages.
 *
 */
// $k->store(['KoalaDB' => 'Products'], [
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
// $k->decrypt('KoalaDB');