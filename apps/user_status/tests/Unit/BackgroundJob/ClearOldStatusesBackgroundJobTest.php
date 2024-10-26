<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\BackgroundJob;

use OCA\UserStatus\BackgroundJob\ClearOldStatusesBackgroundJob;
use OCA\UserStatus\Db\UserStatusMapper;
use OCP\AppFramework\Utility\ITimeFactory;
use Test\TestCase;

class ClearOldStatusesBackgroundJobTest extends TestCase {

	/** @var ITimeFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $time;

	/** @var UserStatusMapper|\PHPUnit\Framework\MockObject\MockObject */
	private $mapper;

	/** @var ClearOldStatusesBackgroundJob */
	private $job;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->mapper = $this->createMock(UserStatusMapper::class);

		$this->job = new ClearOldStatusesBackgroundJob($this->time, $this->mapper);
	}

	public function testRun(): void {
		$this->mapper->expects($this->once())
			->method('clearOlderThanClearAt')
			->with(1337);
		$this->mapper->expects($this->once())
			->method('clearStatusesOlderThan')
			->with(437, 1337);

		$this->time->method('getTime')
			->willReturn(1337);

		self::invokePrivate($this->job, 'run', [[]]);
	}
}
