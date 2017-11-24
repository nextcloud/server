<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Installer;
use OC\SystemConfig;
use OCP\Defaults;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Security\ISecureRandom;

class SetupTest extends \Test\TestCase {

	/** @var SystemConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \bantu\IniGetWrapper\IniGetWrapper|\PHPUnit_Framework_MockObject_MockObject */
	private $iniWrapper;
	/** @var \OCP\IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var \OC\Setup|\PHPUnit_Framework_MockObject_MockObject */
	protected $setupClass;
	/** @var \OCP\ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var \OCP\Security\ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	protected $random;
	/** @var Installer|\PHPUnit_Framework_MockObject_MockObject */
	protected $installer;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->iniWrapper = $this->createMock(IniGetWrapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->defaults = $this->createMock(Defaults::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->installer = $this->createMock(Installer::class);
		$this->setupClass = $this->getMockBuilder('\OC\Setup')
			->setMethods(['class_exists', 'is_callable', 'getAvailableDbDriversForPdo'])
			->setConstructorArgs([$this->config, $this->iniWrapper, $this->l10n, $this->defaults, $this->logger, $this->random, $this->installer])
			->getMock();
	}

	public function testGetSupportedDatabasesWithOneWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'oci')
			));
		$this->setupClass
			->expects($this->once())
			->method('is_callable')
			->will($this->returnValue(false));
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->will($this->returnValue(['sqlite']));
		$result = $this->setupClass->getSupportedDatabases();
		$expectedResult = array(
			'sqlite' => 'SQLite'
		);

		$this->assertSame($expectedResult, $result);
	}

	public function testGetSupportedDatabasesWithNoWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'oci', 'pgsql')
			));
		$this->setupClass
			->expects($this->any())
			->method('is_callable')
			->will($this->returnValue(false));
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->will($this->returnValue([]));
		$result = $this->setupClass->getSupportedDatabases();

		$this->assertSame(array(), $result);
	}

	public function testGetSupportedDatabasesWithAllWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'pgsql', 'oci')
			));
		$this->setupClass
			->expects($this->any())
			->method('is_callable')
			->will($this->returnValue(true));
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->will($this->returnValue(['sqlite', 'mysql', 'pgsql']));
		$result = $this->setupClass->getSupportedDatabases();
		$expectedResult = array(
			'sqlite' => 'SQLite',
			'mysql' => 'MySQL/MariaDB',
			'pgsql' => 'PostgreSQL',
			'oci' => 'Oracle'
		);
		$this->assertSame($expectedResult, $result);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage Supported databases are not properly configured.
	 */
	public function testGetSupportedDatabaseException() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->will($this->returnValue('NotAnArray'));
		$this->setupClass->getSupportedDatabases();
	}
}
