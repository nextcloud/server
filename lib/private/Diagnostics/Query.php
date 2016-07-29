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

use OCP\Diagnostics\IQuery;

class Query implements IQuery {
	private $sql;

	private $params;

	private $start;

	private $end;

	/**
	 * @param string $sql
	 * @param array $params
	 * @param int $start
	 */
	public function __construct($sql, $params, $start) {
		$this->sql = $sql;
		$this->params = $params;
		$this->start = $start;
	}

	public function end($time) {
		$this->end = $time;
	}

	/**
	 * @return array
	 */
	public function getParams() {
		return $this->params;
	}

	/**
	 * @return string
	 */
	public function getSql() {
		return $this->sql;
	}

	/**
	 * @return int
	 */
	public function getDuration() {
		return $this->end - $this->start;
	}
}
