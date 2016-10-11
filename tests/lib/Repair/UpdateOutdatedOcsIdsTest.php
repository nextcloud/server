<?php
/**
 * @author Lukas Reschke <l8kas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace Test\Repair;

use OCP\IConfig;
use Test\TestCase;

/**
 * Class UpdateOutdatedOcsIds
 *
 * @package Test\Repair
 */
class UpdateOutdatedOcsIdsTest extends TestCase {
	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var \OC\Repair\UpdateOutdatedOcsIds */
	private $updateOutdatedOcsIds;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\\OCP\\IConfig')->getMock();
		$this->updateOutdatedOcsIds = new \OC\Repair\UpdateOutdatedOcsIds($this->config);
	}

	public function testGetName() {
		$this->assertSame('Repair outdated OCS IDs', $this->updateOutdatedOcsIds->getName());
	}

	public function testFixOcsIdNoOcsId() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('MyNotInstalledApp', 'ocsid')
			->will($this->returnValue(''));
		$this->assertFalse($this->updateOutdatedOcsIds->fixOcsId('MyNotInstalledApp', '1337', '0815'));
	}

	public function testFixOcsIdUpdateOcsId() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('MyInstalledApp', 'ocsid')
			->will($this->returnValue('1337'));
		$this->config
			->expects($this->at(1))
			->method('setAppValue')
			->with('MyInstalledApp', 'ocsid', '0815');

		$this->assertTrue($this->updateOutdatedOcsIds->fixOcsId('MyInstalledApp', '1337', '0815'));
	}

	public function testFixOcsIdAlreadyFixed() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('MyAlreadyFixedAppId', 'ocsid')
			->will($this->returnValue('0815'));

		$this->assertFalse($this->updateOutdatedOcsIds->fixOcsId('MyAlreadyFixedAppId', '1337', '0815'));
	}
}
