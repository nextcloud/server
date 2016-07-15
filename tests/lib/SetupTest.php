<?php
/**
 * Copyright (c) 2014 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OCP\IConfig;

class SetupTest extends \Test\TestCase {

	/** @var IConfig | \PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var \bantu\IniGetWrapper\IniGetWrapper | \PHPUnit_Framework_MockObject_MockObject */
	private $iniWrapper;
	/** @var \OCP\IL10N | \PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var \OC_Defaults | \PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var \OC\Setup | \PHPUnit_Framework_MockObject_MockObject */
	protected $setupClass;
	/** @var \OCP\ILogger | \PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	/** @var \OCP\Security\ISecureRandom | \PHPUnit_Framework_MockObject_MockObject */
	protected $random;

	protected function setUp() {
		parent::setUp();

		$this->config = $this->getMock('\OCP\IConfig');
		$this->iniWrapper = $this->getMock('\bantu\IniGetWrapper\IniGetWrapper');
		$this->l10n = $this->getMock('\OCP\IL10N');
		$this->defaults = $this->getMock('\OC_Defaults');
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->random = $this->getMock('\OCP\Security\ISecureRandom');
		$this->setupClass = $this->getMock('\OC\Setup',
			['class_exists', 'is_callable', 'getAvailableDbDriversForPdo'],
			[$this->config, $this->iniWrapper, $this->l10n, $this->defaults, $this->logger, $this->random]);
	}

	public function testGetSupportedDatabasesWithOneWorking() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'oci')
			));
		$this->setupClass
			->expects($this->once())
			->method('class_exists')
			->will($this->returnValue(true));
		$this->setupClass
			->expects($this->once())
			->method('is_callable')
			->will($this->returnValue(false));
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->will($this->returnValue([]));
		$result = $this->setupClass->getSupportedDatabases();
		$expectedResult = array(
			'sqlite' => 'SQLite'
		);

		$this->assertSame($expectedResult, $result);
	}

	public function testGetSupportedDatabasesWithNoWorking() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'oci', 'pgsql')
			));
		$this->setupClass
			->expects($this->any())
			->method('class_exists')
			->will($this->returnValue(false));
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
			->method('getSystemValue')
			->will($this->returnValue(
				array('sqlite', 'mysql', 'pgsql', 'oci')
			));
		$this->setupClass
			->expects($this->any())
			->method('class_exists')
			->will($this->returnValue(true));
		$this->setupClass
			->expects($this->any())
			->method('is_callable')
			->will($this->returnValue(true));
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->will($this->returnValue(['mysql', 'pgsql']));
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
			->method('getSystemValue')
			->will($this->returnValue('NotAnArray'));
		$this->setupClass->getSupportedDatabases();
	}
}
