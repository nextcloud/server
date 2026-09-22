<?php

declare(strict_types=1);

namespace OCA\DAV\Tests\unit\BackgroundJob;

use OCA\DAV\BackgroundJob\RegenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\BirthdayService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RegenerateBirthdayCalendarBackgroundJobTest extends TestCase {
	private ITimeFactory&MockObject $time;
	private BirthdayService&MockObject $birthdayService;
	private IConfig&MockObject $config;
	private RegenerateBirthdayCalendarBackgroundJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->birthdayService = $this->createMock(BirthdayService::class);
		$this->config = $this->createMock(IConfig::class);

		$this->job = new RegenerateBirthdayCalendarBackgroundJob(
			$this->time,
			$this->birthdayService,
			$this->config,
		);
	}

	public function testResetAndSyncWhenEnabled(): void {
		$this->birthdayService->expects(self::once())
			->method('resetForUser')
			->with('user123');

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->config->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->birthdayService->expects(self::once())
			->method('syncUser')
			->with('user123');

		$this->job->run(['userId' => 'user123']);
	}

	public function testResetWithoutSyncWhenGloballyDisabled(): void {
		$this->birthdayService->expects(self::once())
			->method('resetForUser')
			->with('user123');

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('no');

		$this->config->expects(self::never())
			->method('getUserValue');

		$this->birthdayService->expects(self::never())
			->method('syncUser');

		$this->job->run(['userId' => 'user123']);
	}

	public function testResetWithoutSyncWhenUserDisabled(): void {
		$this->birthdayService->expects(self::once())
			->method('resetForUser')
			->with('user123');

		$this->config->expects(self::once())
			->method('getAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('yes');

		$this->config->expects(self::once())
			->method('getUserValue')
			->with('user123', 'dav', 'generateBirthdayCalendar', 'yes')
			->willReturn('no');

		$this->birthdayService->expects(self::never())
			->method('syncUser');

		$this->job->run(['userId' => 'user123']);
	}
}
