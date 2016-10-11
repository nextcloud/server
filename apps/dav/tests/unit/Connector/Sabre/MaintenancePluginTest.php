<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\DAV\Tests\unit\Connector\Sabre;

use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use Test\TestCase;
use OCP\IConfig;

/**
 * Class MaintenancePluginTest
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre
 */
class MaintenancePluginTest extends TestCase {
	/** @var IConfig */
	private $config;
	/** @var MaintenancePlugin */
	private $maintenancePlugin;

	public function setUp() {
		parent::setUp();

		$this->config = $this->getMockBuilder('\OCP\IConfig')->getMock();
		$this->maintenancePlugin = new MaintenancePlugin($this->config);
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ServiceUnavailable
	 * @expectedExceptionMessage System in single user mode.
	 */
	public function testSingleUserMode() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('singleuser', false)
			->will($this->returnValue(true));

		$this->maintenancePlugin->checkMaintenanceMode();
	}

	/**
	 * @expectedException \Sabre\DAV\Exception\ServiceUnavailable
	 * @expectedExceptionMessage System in single user mode.
	 */
	public function testMaintenanceMode() {
		$this->config
			->expects($this->exactly(1))
			->method('getSystemValue')
			->will($this->onConsecutiveCalls([false, true]));

		$this->maintenancePlugin->checkMaintenanceMode();
	}

}
