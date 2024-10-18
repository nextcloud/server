<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BackgroundJob;

use OCA\DAV\Paginate\PaginateCache;
use OCP\BackgroundJob\Job;

class CleanupPaginateCacheJob extends Job {
	/** @var PaginateCache */
	private $cache;

	public function __construct(PaginateCache $cache) {
		$this->cache = $cache;
	}

	public function run($argument): void {
		$this->cache->cleanup();
	}
}
