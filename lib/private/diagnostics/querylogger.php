<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Diagnostics;

use OCP\Diagnostics\IQueryLogger;

class QueryLogger implements IQueryLogger {
	/**
	 * @var \OC\Diagnostics\Query
	 */
	protected $activeQuery;

	/**
	 * @var \OC\Diagnostics\Query[]
	 */
	protected $queries = array();

	/**
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 */
	public function startQuery($sql, array $params = null, array $types = null) {
		$this->activeQuery = new Query($sql, $params, microtime(true));
	}

	public function stopQuery() {
		if ($this->activeQuery) {
			$this->activeQuery->end(microtime(true));
			$this->queries[] = $this->activeQuery;
			$this->activeQuery = null;
		}
	}

	/**
	 * @return \OCP\Diagnostics\IQuery[]
	 */
	public function getQueries() {
		return $this->queries;
	}
}
