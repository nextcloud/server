<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

require_once __DIR__ . '/../../../lib/base.php';

/**
 * Class Test_Mount_Config_Dummy_Backend
 */
class Test_Mount_Config_Dummy_Backend {
	public static $checkDependencies = true;

	public static function checkDependencies() {
		return self::$checkDependencies;
	}
}

/**
 * Class Test_Dynamic_Mount_Config
 */
class Test_Dynamic_Mount_Config extends \Test\TestCase {

	private $backup;

	public function testRegistration() {

		// second registration shall return false
		$result = OC_Mount_Config::registerBackend('Test_Mount_Config_Dummy_Backend', array(
			'backend' => 'Test Dummy',
			'configuration' => array(),
			'has_dependencies' => true));

		$this->assertTrue($result);
	}

	public function testDependencyGetBackend() {

		// is the backend listed?
		Test_Mount_Config_Dummy_Backend::$checkDependencies = true;
		$backEnds = OC_Mount_Config::getBackends();
		$this->assertArrayHasKey('Test_Mount_Config_Dummy_Backend', $backEnds);

		// backend shall not be listed
		Test_Mount_Config_Dummy_Backend::$checkDependencies = false;

		$backEnds = OC_Mount_Config::getBackends();
		$this->assertArrayNotHasKey('Test_Mount_Config_Dummy_Backend', $backEnds);

	}

	public function testCheckDependencies() {

		Test_Mount_Config_Dummy_Backend::$checkDependencies = true;
		$message = OC_Mount_Config::checkDependencies();
		$this->assertEmpty($message);

		// backend shall not be listed
		Test_Mount_Config_Dummy_Backend::$checkDependencies = array('dummy');

		$message = OC_Mount_Config::checkDependencies();
		$this->assertEquals('<br /><b>Note:</b> "dummy" is not installed. Mounting of <i>Test Dummy</i> is not possible. Please ask your system administrator to install it.',
			$message);

	}

	protected function setUp() {
		parent::setUp();

		$this->backup = OC_Mount_Config::setUp();

		// register dummy backend
		$result = OC_Mount_Config::registerBackend('Test_Mount_Config_Dummy_Backend', array(
			'backend' => 'Test Dummy',
			'configuration' => array(),
			'has_dependencies' => true));

		$this->assertTrue($result);
	}

	protected function tearDown()
	{
		OC_Mount_Config::setUp($this->backup);
		parent::tearDown();
	}
}
