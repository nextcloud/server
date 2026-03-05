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
			'Regenerating birthday calendars to use new icons and fix old birthday events without year',
			$this->migration->getName()
		);
	}

	public function testRun(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'regeneratedBirthdayCalendarsForYearFix')
			->willReturn(null);

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with('Adding background jobs to regenerate birthday calendar');

		$this->jobList->expects($this->once())
			->method('add')
			->with(RegisterRegenerateBirthdayCalendars::class);

		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'regeneratedBirthdayCalendarsForYearFix', 'yes');

		$this->migration->run($output);
	}

	public function testRunSecondTime(): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('dav', 'regeneratedBirthdayCalendarsForYearFix')
			->willReturn('yes');

		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('info')
			->with('Repair step already executed');

		$this->jobList->expects($this->never())
			->method('add');

		$this->migration->run($output);
	}
}
