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

namespace OCA\UpdateNotification\Tests;

use OCA\UpdateNotification\ResetTokenBackgroundJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use Test\TestCase;

class ResetTokenBackgroundJobTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var ResetTokenBackgroundJob */
	private $resetTokenBackgroundJob;
	/** @var ITimeFactory */
	private $timeFactory;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMock('\\OCP\\IConfig');
		$this->timeFactory = $this->getMock('\\OCP\\AppFramework\\Utility\\ITimeFactory');
		$this->resetTokenBackgroundJob = new ResetTokenBackgroundJob($this->config, $this->timeFactory);
	}

	public function testRunWithNotExpiredToken() {
		$this->timeFactory
			->expects($this->any())
			->method('getTime')
			->willReturn(123);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'updater.secret.created', 123);
		$this->config
			->expects($this->never())
			->method('deleteSystemValue')
			->with('updater.secret');

		$this->invokePrivate($this->resetTokenBackgroundJob, 'run', ['']);
	}

	public function testRunWithExpiredToken() {
		$this->timeFactory
			->expects($this->at(0))
			->method('getTime')
			->willReturn(1455131633);
		$this->timeFactory
			->expects($this->at(1))
			->method('getTime')
			->willReturn(1455045234);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('core', 'updater.secret.created', 1455045234);
		$this->config
			->expects($this->once())
			->method('deleteSystemValue')
			->with('updater.secret');

		$this->invokePrivate($this->resetTokenBackgroundJob, 'run', ['']);
	}
}
