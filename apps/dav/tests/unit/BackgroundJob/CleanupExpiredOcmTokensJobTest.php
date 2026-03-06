<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\CleanupExpiredOcmTokensJob;
use OCA\DAV\Db\OcmTokenMapMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanupExpiredOcmTokensJobTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private OcmTokenMapMapper&MockObject $mapper;
	private CleanupExpiredOcmTokensJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->mapper = $this->createMock(OcmTokenMapMapper::class);

		$this->job = new CleanupExpiredOcmTokensJob($this->timeFactory, $this->mapper);
	}

	public function testRunDeletesExpiredTokens(): void {
		$now = 1700000000;
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn($now);

		$this->mapper->expects($this->once())
			->method('deleteExpired')
			->with($now);

		$method = new \ReflectionMethod(CleanupExpiredOcmTokensJob::class, 'run');
		$method->invoke($this->job, []);
	}
}
