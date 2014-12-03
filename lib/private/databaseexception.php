<?php
/**
 * Copyright (c) 2012 Frank Karlitschek <frank@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

class DatabaseException extends \Exception {
	private $query;

	//FIXME getQuery seems to be unused, maybe use parent constructor with $message, $code and $previous
	public function __construct($message, $query = null){
		parent::__construct($message);
		$this->query = $query;
	}

	public function getQuery() {
		return $this->query;
	}
}
