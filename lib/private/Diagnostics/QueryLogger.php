<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Diagnostics;

use OC\SystemConfig;
use OCP\Cache\CappedMemoryCache;
use OCP\Diagnostics\IQueryLogger;

class QueryLogger implements IQueryLogger {
	/**
	 * Module needs to be activated by some app, e.g. profiler
	 */
	private bool $activated = false;

	protected int $index = 0;

	protected ?Query $activeQuery = null;

	/** @var CappedMemoryCache<Query> */
	protected CappedMemoryCache $queries;

	public function __construct(SystemConfig $config) {
		if ($config->getValue('debug', false)) {
			// In debug mode, module is being activated by default
			$this->activate();
		}
		$this->queries = new CappedMemoryCache(1024);
	}

	#[\Override]
	public function startQuery(string $sql, ?array $params = null, ?array $types = null): void {
		if ($this->activated) {
			$this->activeQuery = new Query($sql, $params, $types, microtime(true), $this->getStack());
		}
	}

	private function getStack(): array {
		return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	}

	#[\Override]
	public function stopQuery(): void {
		if ($this->activated && $this->activeQuery) {
			$this->activeQuery->end(microtime(true));
			$this->queries[(string)$this->index] = $this->activeQuery;
			$this->index++;
			$this->activeQuery = null;
		}
	}

	#[\Override]
	public function getQueries(): array {
		return $this->queries->getData();
	}

	#[\Override]
	public function activate(): void {
		$this->activated = true;
	}
}
