<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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
