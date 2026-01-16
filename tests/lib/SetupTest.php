<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\Installer;
use OC\Setup;
use OC\SystemConfig;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IL10N;
use OCP\Install\Events\InstallationCompletedEvent;
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
	protected IEventDispatcher $eventDispatcher;

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
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->setupClass = $this->getMockBuilder(Setup::class)
			->onlyMethods(['class_exists', 'is_callable', 'getAvailableDbDriversForPdo'])
			->setConstructorArgs([$this->config, $this->iniWrapper, $this->l10nFactory, $this->defaults, $this->logger, $this->random, $this->installer, $this->eventDispatcher])
			->getMock();
	}

	public function testGetSupportedDatabasesWithOneWorking(): void {
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

	public function testGetSupportedDatabasesWithNoWorking(): void {
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

	public function testGetSupportedDatabasesWithAllWorking(): void {
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


	public function testGetSupportedDatabaseException(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Supported databases are not properly configured.');

		$this->config
			->expects($this->once())
			->method('getValue')
			->willReturn('NotAnArray');
		$this->setupClass->getSupportedDatabases();
	}

	/**
	 * @param $url
	 * @param $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('findWebRootProvider')]
	public function testFindWebRootCli($url, $expected): void {
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

	public static function findWebRootProvider(): array {
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

	/**
	 * Test that Setup class has eventDispatcher injected
	 */
	public function testSetupHasEventDispatcher(): void {
		$reflectionClass = new \ReflectionClass($this->setupClass);
		$property = $reflectionClass->getProperty('eventDispatcher');
		$property->setAccessible(true);

		$eventDispatcher = $property->getValue($this->setupClass);

		$this->assertInstanceOf(IEventDispatcher::class, $eventDispatcher);
	}

	/**
	 * Helper method to extract event parameters from install options
	 * This mirrors the logic in Setup::install() for extracting dataDir and admin parameters
	 *
	 * Note: This assumes 'directory' key is present in options. Setup::install() has a fallback
	 * that sets a default directory if empty, but our tests always provide this key.
	 */
	private function extractInstallationEventParameters(array $options): array {
		$dataDir = htmlspecialchars_decode($options['directory']);
		$disableAdminUser = (bool)($options['admindisable'] ?? false);
		$adminUsername = !$disableAdminUser ? ($options['adminlogin'] ?? null) : null;
		$adminEmail = !empty($options['adminemail']) ? $options['adminemail'] : null;

		return [$dataDir, $adminUsername, $adminEmail];
	}

	/**
	 * Test that InstallationCompletedEvent can be created with parameters from install options
	 *
	 * This test verifies that the InstallationCompletedEvent can be properly constructed with
	 * the parameters that Setup::install() extracts from the options array for dataDir and admin parameters.
	 *
	 * Note: Testing that Setup::install() actually dispatches this event requires a full integration
	 * test with database setup, file system operations, and app installation, which is beyond the
	 * scope of a unit test. The event class itself is thoroughly tested in InstallationCompletedEventTest.php.
	 */
	public function testInstallationCompletedEventParametersFromInstallOptions(): void {
		// Simulate the options array as passed to Setup::install()
		$options = [
			'directory' => '/path/to/data',
			'adminlogin' => 'admin',
			'adminemail' => 'admin@example.com',
		];

		// Extract parameters the same way Setup::install() does
		[$dataDir, $adminUsername, $adminEmail] = $this->extractInstallationEventParameters($options);

		// Create the event as Setup::install() does after successful installation
		$event = new InstallationCompletedEvent($dataDir, $adminUsername, $adminEmail);

		// Verify the event contains the expected values
		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertEquals($adminUsername, $event->getAdminUsername());
		$this->assertEquals($adminEmail, $event->getAdminEmail());
		$this->assertTrue($event->hasAdminUser());
	}

	/**
	 * Test that event parameters handle disabled admin user correctly
	 *
	 * This tests the scenario where Setup::install() is called with admindisable=true,
	 * resulting in a null adminUsername in the event.
	 */
	public function testInstallationCompletedEventWithDisabledAdminUser(): void {
		$options = [
			'directory' => '/path/to/data',
			'admindisable' => true,
		];

		// Extract parameters as Setup::install() does
		[$dataDir, $adminUsername, $adminEmail] = $this->extractInstallationEventParameters($options);

		$event = new InstallationCompletedEvent($dataDir, $adminUsername, $adminEmail);

		$this->assertEquals($dataDir, $event->getDataDirectory());
		$this->assertNull($event->getAdminUsername());
		$this->assertNull($event->getAdminEmail());
		$this->assertFalse($event->hasAdminUser());
	}
}
