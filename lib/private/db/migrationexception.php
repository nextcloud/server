<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\DB;


class MigrationException extends \Exception {
	private $table;

	public function __construct($table, $message) {
		$this->table = $table;
		parent::__construct($message);
	}

	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}
}
