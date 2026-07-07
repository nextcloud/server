<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Tests\BackgroundJob;

use OCA\CloudFederationAPI\BackgroundJob\CleanupExpiredOcmTokensJob;
use OCA\CloudFederationAPI\Service\OcmTokenService;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CleanupExpiredOcmTokensJobTest extends TestCase {
	private ITimeFactory&MockObject $timeFactory;
	private OcmTokenService&MockObject $tokenService;
	private CleanupExpiredOcmTokensJob $job;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->tokenService = $this->createMock(OcmTokenService::class);

		$this->job = new CleanupExpiredOcmTokensJob($this->timeFactory, $this->tokenService);
	}

	public function testRunRevokesExpiredAtCurrentTime(): void {
		$now = 1700000000;
		$this->timeFactory->method('getTime')->willReturn($now);
		$this->tokenService->expects($this->once())
			->method('revokeExpired')->with($now);

		$method = new \ReflectionMethod(CleanupExpiredOcmTokensJob::class, 'run');
		$method->invoke($this->job, []);
	}
}
