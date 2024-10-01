<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Diagnostics;

use OCP\Cache\CappedMemoryCache;
use OCP\Diagnostics\IQueryLogger;

class QueryLogger implements IQueryLogger {
	protected int $index = 0;
	protected ?Query $activeQuery = null;
	/** @var CappedMemoryCache<Query> */
	protected CappedMemoryCache $queries;

	/**
	 * QueryLogger constructor.
	 */
	public function __construct() {
		$this->queries = new CappedMemoryCache(1024);
	}


	/**
	 * @var bool - Module needs to be activated by some app
	 */
	private $activated = false;

	/**
	 * @inheritdoc
	 */
	public function startQuery($sql, ?array $params = null, ?array $types = null) {
		if ($this->activated) {
			$this->activeQuery = new Query($sql, $params, microtime(true), $this->getStack());
		}
	}

	private function getStack() {
		$stack = debug_backtrace();
		array_shift($stack);
		array_shift($stack);
		array_shift($stack);
		return $stack;
	}

	/**
	 * @inheritdoc
	 */
	public function stopQuery() {
		if ($this->activated && $this->activeQuery) {
			$this->activeQuery->end(microtime(true));
			$this->queries[(string)$this->index] = $this->activeQuery;
			$this->index++;
			$this->activeQuery = null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function getQueries() {
		return $this->queries->getData();
	}

	/**
	 * @inheritdoc
	 */
	public function activate() {
		$this->activated = true;
	}
}
