<?php
/**
 * @copyright Copyright (c) 2018, Georg Ehrke
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author François Freitag <mail@franek.fr>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	/** @var IJobList | MockObject */
	private $jobList;

	/** @var IConfig | MockObject */
	private $config;

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

	public function dataForTestRun(): array {
		return [
			['', '', true],
			['yes', '', true],
			['yes', 'yes', false]
		];
	}

	/**
	 * @dataProvider dataForTestRun
	 */
	public function testRun(string $yearFix, string $alarmFix, bool $run): void {
		$this->config->expects($this->exactly($yearFix === '' ? 1 : 2))
			->method('getAppValue')
			->withConsecutive(['dav', 'regeneratedBirthdayCalendarsForYearFix'], ['dav', 'regeneratedBirthdayCalendarsForAlarmFix'])
			->willReturnOnConsecutiveCalls($yearFix, $alarmFix);

		$output = $this->createMock(IOutput::class);

		if ($run) {
			$output->expects($this->once())
				->method('info')
				->with('Adding background jobs to regenerate birthday calendar');

			$this->jobList->expects($this->once())
				->method('add')
				->with(RegisterRegenerateBirthdayCalendars::class);

			$this->config->expects($this->exactly(2))
				->method('setAppValue')
				->withConsecutive(['dav', 'regeneratedBirthdayCalendarsForYearFix', 'yes'], ['dav', 'regeneratedBirthdayCalendarsForAlarmFix', 'yes']);
		} else {
			$output->expects($this->once())
				->method('info')
				->with('Repair step already executed');

			$this->jobList->expects($this->never())
				->method('add');

			$this->config->expects($this->never())->method('setAppValue');
		}

		$this->migration->run($output);
	}
}
