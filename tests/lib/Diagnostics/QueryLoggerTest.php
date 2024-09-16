<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Diagnostics;

use OC\Diagnostics\QueryLogger;
use Test\TestCase;

class QueryLoggerTest extends TestCase {
	/** @var \OC\Diagnostics\QueryLogger */
	private $logger;
	
	protected function setUp(): void {
		parent::setUp();

		$this->logger = new QueryLogger();
	}

	public function testQueryLogger(): void {
		// Module is not activated and this should not be logged
		$this->logger->startQuery('SELECT', ['testuser', 'count'], ['string', 'int']);
		$this->logger->stopQuery();
		$queries = $this->logger->getQueries();
		$this->assertSame(0, sizeof($queries));

		// Activate module and log some query
		$this->logger->activate();
		$this->logger->startQuery('SELECT', ['testuser', 'count'], ['string', 'int']);
		$this->logger->stopQuery();

		$queries = $this->logger->getQueries();
		$this->assertSame(1, sizeof($queries));
	}
}
