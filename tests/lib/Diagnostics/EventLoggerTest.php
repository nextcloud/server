<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2017 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Diagnostics;

use OC\Diagnostics\EventLogger;
use OC\Log;
use OC\SystemConfig;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class EventLoggerTest extends TestCase {
	/** @var \OC\Diagnostics\EventLogger */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = new EventLogger(
			$this->createMock(SystemConfig::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(Log::class)
		);
	}

	public function testQueryLogger(): void {
		// Module is not activated and this should not be logged
		$this->logger->start('test1', 'testevent1');
		$this->logger->end('test1');
		$this->logger->log('test2', 'testevent2', microtime(true), microtime(true));
		$events = $this->logger->getEvents();
		$this->assertSame(0, sizeof($events));

		// Activate module and log some query
		$this->logger->activate();

		// start one event
		$this->logger->start('test3', 'testevent3');

		// force log of another event
		$this->logger->log('test4', 'testevent4', microtime(true), microtime(true));

		// log started event
		$this->logger->end('test3');

		$events = $this->logger->getEvents();
		$this->assertSame('test4', $events['test4']->getId());
		$this->assertSame('testevent4', $events['test4']->getDescription());
		$this->assertSame('test3', $events['test3']->getId());
		$this->assertSame('testevent3', $events['test3']->getDescription());
		$this->assertSame(2, sizeof($events));
	}
}
