<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Diagnostics\IQuery;

class Query implements IQuery {
	private $end;

	/**
	 * @param string $sql
	 * @param array $params
	 * @param int $start
	 */
	public function __construct(
		private $sql,
		private $params,
		private $start,
		private array $stack,
	) {
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
	 * @return float
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @return float
	 */
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
