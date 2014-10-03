<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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
