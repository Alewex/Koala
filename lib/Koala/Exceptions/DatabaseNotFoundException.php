<?php

namespace Koala\Exceptions;

class DatabaseNotFoundException extends \Exception {

	public function __construct($message) {
		parent::__construct($message);
	}

}