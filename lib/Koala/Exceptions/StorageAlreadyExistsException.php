<?php

namespace Koala\Exceptions;

class StorageAlreadyExistsException extends \Exception {

	public function __construct($message) {
		parent::__construct($message);
	}

}