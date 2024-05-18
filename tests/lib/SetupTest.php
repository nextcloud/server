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
use OC\Setup;
use OC\SystemConfig;
use OCP\Defaults;
use OCP\IL10N;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Security\ISecureRandom;
use Psr\Log\LoggerInterface;

class SetupTest extends \Test\TestCase {
	protected SystemConfig $config;
	private IniGetWrapper $iniWrapper;
	private IL10N $l10n;
	private IL10NFactory $l10nFactory;
	private Defaults $defaults;
	protected Setup $setupClass;
	protected LoggerInterface $logger;
	protected ISecureRandom $random;
	protected Installer $installer;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(SystemConfig::class);
		$this->iniWrapper = $this->createMock(IniGetWrapper::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IL10NFactory::class);
		$this->l10nFactory->method('get')
			->willReturn($this->l10n);
		$this->defaults = $this->createMock(Defaults::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->installer = $this->createMock(Installer::class);
		$this->setupClass = $this->getMockBuilder(Setup::class)
			->setMethods(['class_exists', 'is_callable', 'getAvailableDbDriversForPdo'])
			->setConstructorArgs([$this->config, $this->iniWrapper, $this->l10nFactory, $this->defaults, $this->logger, $this->random, $this->installer])
			->getMock();
	}

	public function testGetSupportedDatabasesWithOneWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn(
				['sqlite', 'mysql', 'oci']
			);
		$this->setupClass
			->expects($this->once())
			->method('is_callable')
			->willReturn(false);
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->willReturn(['sqlite']);
		$result = $this->setupClass->getSupportedDatabases();
		$expectedResult = [
			'sqlite' => 'SQLite'
		];

		$this->assertSame($expectedResult, $result);
	}

	public function testGetSupportedDatabasesWithNoWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn(
				['sqlite', 'mysql', 'oci', 'pgsql']
			);
		$this->setupClass
			->expects($this->any())
			->method('is_callable')
			->willReturn(false);
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->willReturn([]);
		$result = $this->setupClass->getSupportedDatabases();

		$this->assertSame([], $result);
	}

	public function testGetSupportedDatabasesWithAllWorking() {
		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn(
				['sqlite', 'mysql', 'pgsql', 'oci']
			);
		$this->setupClass
			->expects($this->any())
			->method('is_callable')
			->willReturn(true);
		$this->setupClass
			->expects($this->any())
			->method('getAvailableDbDriversForPdo')
			->willReturn(['sqlite', 'mysql', 'pgsql']);
		$result = $this->setupClass->getSupportedDatabases();
		$expectedResult = [
			'sqlite' => 'SQLite',
			'mysql' => 'MySQL/MariaDB',
			'pgsql' => 'PostgreSQL',
			'oci' => 'Oracle'
		];
		$this->assertSame($expectedResult, $result);
	}


	public function testGetSupportedDatabaseException() {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Supported databases are not properly configured.');

		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn('NotAnArray');
		$this->setupClass->getSupportedDatabases();
	}

	/**
	 * @dataProvider findWebRootProvider
	 * @param $url
	 * @param $expected
	 */
	public function testFindWebRootCli($url, $expected) {
		$cliState = \OC::$CLI;

		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn($url);
		\OC::$CLI = true;

		try {
			$webRoot = self::invokePrivate($this->setupClass, 'findWebRoot', [$this->config]);
		} catch (\InvalidArgumentException $e) {
			$webRoot = false;
		}

		\OC::$CLI = $cliState;
		$this->assertSame($webRoot, $expected);
	}

	public function findWebRootProvider(): array {
		return [
			'https://www.example.com/nextcloud/' => ['https://www.example.com/nextcloud/', '/nextcloud'],
			'https://www.example.com/nextcloud' => ['https://www.example.com/nextcloud', '/nextcloud'],
			'https://www.example.com/' => ['https://www.example.com/', ''],
			'https://www.example.com' => ['https://www.example.com', ''],
			'https://nctest13pgsql.lan/test123/' => ['https://nctest13pgsql.lan/test123/', '/test123'],
			'https://nctest13pgsql.lan/test123' => ['https://nctest13pgsql.lan/test123', '/test123'],
			'https://nctest13pgsql.lan/' => ['https://nctest13pgsql.lan/', ''],
			'https://nctest13pgsql.lan' => ['https://nctest13pgsql.lan', ''],
			'https://192.168.10.10/nc/' => ['https://192.168.10.10/nc/', '/nc'],
			'https://192.168.10.10/nc' => ['https://192.168.10.10/nc', '/nc'],
			'https://192.168.10.10/' => ['https://192.168.10.10/', ''],
			'https://192.168.10.10' => ['https://192.168.10.10', ''],
			'invalid' => ['invalid', false],
			'empty' => ['', false],
		];
	}
}
