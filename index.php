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
/**
 *
 * Storing data om storages.
 *
 */
$k->store(['KoalaDB' => 'Users']);