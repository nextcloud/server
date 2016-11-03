<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Diagnostics;

use OC\Cache\CappedMemoryCache;
use OCP\Diagnostics\IQueryLogger;

class QueryLogger implements IQueryLogger {
	/**
	 * @var \OC\Diagnostics\Query
	 */
	protected $activeQuery;

	/**
	 * @var \OC\Diagnostics\Query[]
	 */
	protected $queries;

	/**
	 * QueryLogger constructor.
	 */
	public function __construct() {
		$this->queries = new CappedMemoryCache(1024);
	}


	/**
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 */
	public function startQuery($sql, array $params = null, array $types = null) {
		$this->activeQuery = new Query($sql, $params, microtime(true), $this->getStack());
	}

	private function getStack() {
		$stack = debug_backtrace();
		array_shift($stack);
		array_shift($stack);
		array_shift($stack);
		return $stack;
	}

	public function stopQuery() {
		if ($this->activeQuery) {
			$this->activeQuery->end(microtime(true));
			$this->queries[] = $this->activeQuery;
			$this->activeQuery = null;
		}
	}

	/**
	 * @return Query[]
	 */
	public function getQueries() {
		return $this->queries->getData();
	}
}
