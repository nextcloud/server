<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\BirthdayService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class GenerateBirthdayCalendarBackgroundJobTest extends TestCase {

	/** @var ITimeFactory|MockObject */
	private $time;

	/** @var BirthdayService | MockObject */
	private $birthdayService;

	/** @var IConfig | MockObject */
	private $config;

	/** @var GenerateBirthdayCalendarBackgroundJob */
	private $backgroundJob;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->birthdayService = $this->createMock(BirthdayService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->backgroundJob = new GenerateBirthdayCalendarBackgroundJob(
			$this->time,
			$this->birthdayService,
			$this->config,
		);
	}

	public function testRun(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->birthdayService->expects($this->never())
			->method('resetForUser')
			->with('user123');

		$this->birthdayService->expects($this->once())
			->method('syncUser')
			->with('user123');

		$this->backgroundJob->run(['userId' => 'user123']);
	}

	public function testRunAndReset(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->birthdayService->expects($this->once())
			->method('resetForUser')
			->with('user123');

		$this->birthdayService->expects($this->once())
			->method('syncUser')
			->with('user123');

		$this->backgroundJob->run(['userId' => 'user123', 'purgeBeforeGenerating' => true]);
	}

	public function testRunGloballyDisabled(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('no');

		$this->config->expects($this->never())
			->method('getUserValue');

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->backgroundJob->run(['userId' => 'user123']);
	}

	public function testRunUserDisabled(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('no');

		$this->birthdayService->expects($this->never())
			->method('syncUser');

		$this->backgroundJob->run(['userId' => 'user123']);
	}
}
