<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\DAV\Migration;

use OCA\DAV\BackgroundJob\RegisterRegenerateBirthdayCalendars;
use OCA\DAV\Migration\RegenerateBirthdayCalendars;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RegenerateBirthdayCalendarsTest extends TestCase {
	private IJobList&MockObject $jobList;
	private IConfig&MockObject $config;
	private RegenerateBirthdayCalendars $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->config = $this->createMock(IConfig::class);

		$this->migration = new RegenerateBirthdayCalendars($this->jobList,
			$this->config);
	}

	public function testGetName(): void {
		$this->assertEquals(
			'Regenerating birthday calendars to use new icons, fix old birthday events without year and update reminders',
			$this->migration->getName()
		);
	}

	public static function dataForTestRun(): array {
		return [
			['', '', true],
			['yes', '', true],
			['yes', 'yes', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataForTestRun')]
	public function testRun(string $yearFix, string $alarmFix, bool $run): void {
		// The alarm fix is only looked up when the year fix already ran
		$this->config->expects($this->exactly($yearFix === 'yes' ? 2 : 1))
			->method('getAppValue')
			->willReturnCallback(function (string $app, string $key) use ($yearFix, $alarmFix): string {
				$this->assertEquals('dav', $app);
				return match ($key) {
					'regeneratedBirthdayCalendarsForYearFix' => $yearFix,
					'regeneratedBirthdayCalendarsForAlarmFix' => $alarmFix,
				};
			});

		$output = $this->createMock(IOutput::class);
		$setAppValueCalls = [];

		if ($run) {
			$output->expects($this->once())
				->method('info')
				->with('Adding background jobs to regenerate birthday calendar');

			$this->jobList->expects($this->once())
				->method('add')
				->with(RegisterRegenerateBirthdayCalendars::class);

			$this->config->expects($this->exactly(2))
				->method('setAppValue')
				->willReturnCallback(function (string $app, string $key, string $value) use (&$setAppValueCalls): void {
					$setAppValueCalls[] = [$app, $key, $value];
				});
		} else {
			$output->expects($this->once())
				->method('info')
				->with('Repair step already executed');

			$this->jobList->expects($this->never())
				->method('add');

			$this->config->expects($this->never())
				->method('setAppValue');
		}

		$this->migration->run($output);

		if ($run) {
			$this->assertEquals([
				['dav', 'regeneratedBirthdayCalendarsForYearFix', 'yes'],
				['dav', 'regeneratedBirthdayCalendarsForAlarmFix', 'yes'],
			], $setAppValueCalls);
		}
	}
}
