<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;

class Adapter {
	protected $conn;

	public function __construct($conn) {
		$this->conn = $conn;
	}

	public function lastInsertId($table) {
		return $this->conn->realLastInsertId($table);
	}

	public function fixupStatement($statement) {
		return $statement;
	}
}
