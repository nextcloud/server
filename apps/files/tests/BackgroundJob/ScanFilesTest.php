<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files\Tests\BackgroundJob;

use Test\TestCase;
use OCP\IConfig;
use OCP\IUserManager;
use OCA\Files\BackgroundJob\ScanFiles;

/**
 * Class ScanFilesTest
 *
 * @package OCA\Files\Tests\BackgroundJob
 */
class ScanFilesTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var IUserManager */
	private $userManager;
	/** @var ScanFiles */
	private $scanFiles;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMock('\OCP\IConfig');
		$this->userManager = $this->getMock('\OCP\IUserManager');

		$this->scanFiles = $this->getMockBuilder('\OCA\Files\BackgroundJob\ScanFiles')
				->setConstructorArgs([
						$this->config,
						$this->userManager,
				])
				->setMethods(['runScanner'])
				->getMock();
	}

	public function testRunWithoutUsers() {
		$this->config
				->expects($this->at(0))
				->method('getAppValue')
				->with('files', 'cronjob_scan_files', 0)
				->will($this->returnValue(50));
		$this->userManager
				->expects($this->at(0))
				->method('search')
				->with('', 500, 50)
				->will($this->returnValue([]));
		$this->userManager
				->expects($this->at(1))
				->method('search')
				->with('', 500)
				->will($this->returnValue([]));
		$this->config
				->expects($this->at(1))
				->method('setAppValue')
				->with('files', 'cronjob_scan_files', 500);

		$this->invokePrivate($this->scanFiles, 'run', [[]]);
	}

	public function testRunWithUsers() {
		$fakeUser = $this->getMock('\OCP\IUser');
		$this->config
				->expects($this->at(0))
				->method('getAppValue')
				->with('files', 'cronjob_scan_files', 0)
				->will($this->returnValue(50));
		$this->userManager
				->expects($this->at(0))
				->method('search')
				->with('', 500, 50)
				->will($this->returnValue([
						$fakeUser
				]));
		$this->config
				->expects($this->at(1))
				->method('setAppValue')
				->with('files', 'cronjob_scan_files', 550);
		$this->scanFiles
				->expects($this->once())
				->method('runScanner')
				->with($fakeUser);

		$this->invokePrivate($this->scanFiles, 'run', [[]]);
	}

	public function testRunWithUsersAndOffsetAtEndOfUserList() {
		$this->config
				->expects($this->at(0))
				->method('getAppValue')
				->with('files', 'cronjob_scan_files', 0)
				->will($this->returnValue(50));
		$this->userManager
				->expects($this->at(0))
				->method('search')
				->with('', 500, 50)
				->will($this->returnValue([]));
		$this->userManager
				->expects($this->at(1))
				->method('search')
				->with('', 500)
				->will($this->returnValue([]));
		$this->config
				->expects($this->at(1))
				->method('setAppValue')
				->with('files', 'cronjob_scan_files', 500);
		$this->scanFiles
				->expects($this->never())
				->method('runScanner');

		$this->invokePrivate($this->scanFiles, 'run', [[]]);
	}

}
