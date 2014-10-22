<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Diagnostics;

use OCP\Diagnostics\IQueryLogger;

class NullQueryLogger implements IQueryLogger {
	/**
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 */
	public function startQuery($sql, array $params = null, array $types = null) {
	}

	public function stopQuery() {
	}

	/**
	 * @return \OCP\Diagnostics\IQuery[]
	 */
	public function getQueries() {
		return array();
	}
}
