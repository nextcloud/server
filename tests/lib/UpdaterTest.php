<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
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

namespace Test;

use OC\Updater;
use OCP\IConfig;
use OCP\ILogger;
use OC\IntegrityCheck\Checker;

class UpdaterTest extends \Test\TestCase {
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var Updater */
	private $updater;
	/** @var Checker */
	private $checker;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder('\\OCP\\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$this->logger = $this->getMockBuilder('\\OCP\\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->checker = $this->getMockBuilder('\OC\IntegrityCheck\Checker')
				->disableOriginalConstructor()
				->getMock();

		$this->updater = new Updater(
			$this->config,
			$this->checker,
			$this->logger
		);
	}

	/**
	 * @param string $baseUrl
	 * @return string
	 */
	private function buildUpdateUrl($baseUrl) {
		return $baseUrl . '?version='.implode('x', \OCP\Util::getVersion()).'xinstalledatxlastupdatedatx'.\OC_Util::getChannel().'x'.\OC_Util::getEditionString().'x';
	}

	/**
	 * @return array
	 */
	public function versionCompatibilityTestData() {
		return [
			['1', '2', '1', true],
			['2', '2', '2', true],
			['6.0.5.0', '6.0.6.0', '5.0', true],
			['5.0.6.0', '7.0.4.0', '6.0', false],
			// allow upgrading within the same major release
			['8.0.0.0', '8.0.0.0', '8.0', true],
			['8.0.0.0', '8.0.0.4', '8.0', true],
			['8.0.0.0', '8.0.1.0', '8.0', true],
			['8.0.0.0', '8.0.2.0', '8.0', true],
			// does not allow downgrading within the same major release
			['8.0.1.0', '8.0.0.0', '8.0', false],
			['8.0.2.0', '8.0.1.0', '8.0', false],
			['8.0.0.4', '8.0.0.0', '8.0', false],
			// allows upgrading within the patch version
			['8.0.0.0', '8.0.0.1', '8.0', true],
			['8.0.0.0', '8.0.0.2', '8.0', true],
			// does not allow downgrading within the same major release
			['8.0.0.1', '8.0.0.0', '8.0', false],
			['8.0.0.2', '8.0.0.0', '8.0', false],
			// allow upgrading to the next major release
			['8.0.0.0', '8.1.0.0', '8.0', true],
			['8.0.0.0', '8.1.1.0', '8.0', true],
			['8.0.0.0', '8.1.1.5', '8.0', true],
			['8.0.0.2', '8.1.1.5', '8.0', true],
			['8.1.0.0', '8.2.0.0', '8.1', true],
			['8.1.0.2', '8.2.0.4', '8.1', true],
			['8.1.0.5', '8.2.0.1', '8.1', true],
			['8.1.0.0', '8.2.1.0', '8.1', true],
			['8.1.0.2', '8.2.1.5', '8.1', true],
			['8.1.0.5', '8.2.1.1', '8.1', true],
			// does not allow downgrading to the previous major release
			['8.1.0.0', '8.0.0.0', '7.0', false],
			['8.1.1.0', '8.0.0.0', '7.0', false],
			// does not allow skipping major releases
			['8.0.0.0', '8.2.0.0', '8.1', false],
			['8.0.0.0', '8.2.1.0', '8.1', false],
			['8.0.0.0', '9.0.1.0', '8.2', false],
			['8.0.0.0', '10.0.0.0', '9.3', false],
			// allows updating to the next major release
			['8.2.0.0', '9.0.0.0', '8.2', true],
			['8.2.0.0', '9.0.0.0', '8.2', true],
			['8.2.0.0', '9.0.1.0', '8.2', true],
			['8.2.0.0', '9.0.1.1', '8.2', true],
			['8.2.0.2', '9.0.1.1', '8.2', true],
			['8.2.2.0', '9.0.1.0', '8.2', true],
			['8.2.2.2', '9.0.1.1', '8.2', true],
			['9.0.0.0', '9.1.0.0', '9.0', true],
			['9.0.0.0', '9.1.0.2', '9.0', true],
			['9.0.0.2', '9.1.0.1', '9.0', true],
			['9.1.0.0', '9.2.0.0', '9.1', true],
			['9.2.0.0', '9.3.0.0', '9.2', true],
			['9.3.0.0', '10.0.0.0', '9.3', true],
			// does not allow updating to the next major release (first number)
			['9.0.0.0', '8.2.0.0', '8.1', false],
			// other cases
			['8.0.0.0', '8.1.5.0', '8.0', true],
			['8.2.0.0', '9.0.0.0', '8.2', true],
			['8.2.0.0', '9.1.0.0', '9.0', false],
			['9.0.0.0', '8.1.0.0', '8.0', false],
			['9.0.0.0', '8.0.0.0', '7.0', false],
			['9.1.0.0', '8.0.0.0', '7.0', false],
			['8.2.0.0', '8.1.0.0', '8.0', false],

			// With debug enabled
			['8.0.0.0', '8.2.0.0', '8.1', false, true],
			['8.1.0.0', '8.2.0.0', '8.1', true, true],
			['8.2.0.1', '8.2.0.1', '8.1', true, true],
			['8.3.0.0', '8.2.0.0', '8.1', true, true],

			// Downgrade of maintenance
			['9.0.53.0', '9.0.4.0', '8.1', false, false, 'nextcloud'],
			// with vendor switch
			['9.0.53.0', '9.0.4.0', '8.1', true, false, ''],
			['9.0.53.0', '9.0.4.0', '8.1', true, false, 'owncloud'],
		];
	}

	/**
	 * @dataProvider versionCompatibilityTestData
	 *
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @param string $allowedVersion
	 * @param bool $result
	 * @param bool $debug
	 * @param string $vendor
	 */
	public function testIsUpgradePossible($oldVersion, $newVersion, $allowedVersion, $result, $debug = false, $vendor = 'nextcloud') {
		$this->config->expects($this->any())
			->method('getSystemValue')
			->with('debug', false)
			->willReturn($debug);
		$this->config->expects($this->any())
			->method('getAppValue')
			->with('core', 'vendor', '')
			->willReturn($vendor);

		$this->assertSame($result, $this->updater->isUpgradePossible($oldVersion, $newVersion, $allowedVersion));
	}

	public function testSetSimulateStepEnabled() {
		$this->updater->setSimulateStepEnabled(true);
		$this->assertSame(true, $this->invokePrivate($this->updater, 'simulateStepEnabled'));
		$this->updater->setSimulateStepEnabled(false);
		$this->assertSame(false, $this->invokePrivate($this->updater, 'simulateStepEnabled'));
	}

	public function testSetUpdateStepEnabled() {
		$this->updater->setUpdateStepEnabled(true);
		$this->assertSame(true, $this->invokePrivate($this->updater, 'updateStepEnabled'));
		$this->updater->setUpdateStepEnabled(false);
		$this->assertSame(false, $this->invokePrivate($this->updater, 'updateStepEnabled'));
	}

	public function testSetSkip3rdPartyAppsDisable() {
		$this->updater->setSkip3rdPartyAppsDisable(true);
		$this->assertSame(true, $this->invokePrivate($this->updater, 'skip3rdPartyAppsDisable'));
		$this->updater->setSkip3rdPartyAppsDisable(false);
		$this->assertSame(false, $this->invokePrivate($this->updater, 'skip3rdPartyAppsDisable'));
	}

}
