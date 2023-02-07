<?php
/**
 * @copyright 2021 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\Files_Versions\Tests\BackgroundJob;

use OCA\Files_Versions\BackgroundJob\ExpireVersions;
use OCA\Files_Versions\Expiration;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExpireVersionsTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var Expiration|MockObject */
	private $expiration;

	/** @var IJobList|MockObject */
	private $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testBackgroundJobDeactivated(): void {
		$this->config->method('getAppValue')
			->with('files_versions', 'background_job_expire_versions', 'yes')
			->willReturn('no');
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$timeFactory = $this->createMock(ITimeFactory::class);
		$timeFactory->method('getTime')
			->with()
			->willReturn(999999999);

		$job = new ExpireVersions($this->config, $this->userManager, $this->expiration, $timeFactory);
		$job->start($this->jobList);
	}
}
