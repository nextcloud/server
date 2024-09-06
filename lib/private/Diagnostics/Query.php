<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Diagnostics\IQuery;

class Query implements IQuery {
	private $sql;

	private $params;

	private $start;

	private $end;

	private $stack;

	/**
	 * @param string $sql
	 * @param array $params
	 * @param int $start
	 */
	public function __construct($sql, $params, $start, array $stack) {
		$this->sql = $sql;
		$this->params = $params;
		$this->start = $start;
		$this->stack = $stack;
	}

	public function end($time) {
		$this->end = $time;
	}

	public function getParams() {
		return $this->params;
	}

	public function getSql() {
		return $this->sql;
	}

	public function getStart() {
		return $this->start;
	}
	
	public function getDuration() {
		return $this->end - $this->start;
	}

	public function getStartTime() {
		return $this->start;
	}

	public function getStacktrace() {
		return $this->stack;
	}
}
