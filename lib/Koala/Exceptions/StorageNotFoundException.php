<?php

namespace Koala\Exceptions;

class StorageNotFoundException extends \Exception {

	public function __construct($message) {
		parent::__construct($message);
	}

}