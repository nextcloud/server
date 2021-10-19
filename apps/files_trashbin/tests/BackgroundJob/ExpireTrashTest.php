<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Trashbin\Tests\BackgroundJob;

use OCA\Files_Trashbin\BackgroundJob\ExpireTrash;
use OCA\Files_Trashbin\Expiration;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ExpireTrashTest extends TestCase {
	/** @var IConfig|MockObject */
	private $config;

	/** @var IUserManager|MockObject */
	private $userManager;

	/** @var Expiration|MockObject */
	private $expiration;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var ILogger|MockObject */
	private $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->expiration = $this->createMock(Expiration::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->jobList->expects($this->once())
			->method('setLastRun');
		$this->jobList->expects($this->once())
			->method('setExecutionTime');
	}

	public function testConstructAndRun(): void {
		$job = new ExpireTrash($this->config, $this->userManager, $this->expiration);
		$job->execute($this->jobList, $this->logger);
	}

	public function testBackgroundJobDeactivated(): void {
		$this->config->method('getAppValue')
			->with('files_trashbin', 'background_job_expire_trash', 'yes')
			->willReturn('no');
		$this->expiration->expects($this->never())
			->method('getMaxAgeAsTimestamp');

		$job = new ExpireTrash($this->config, $this->userManager, $this->expiration);
		$job->execute($this->jobList, $this->logger);
	}
}
