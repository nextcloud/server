<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\Installer;
use OC\IntegrityCheck\Checker;
use OC\Updater;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class UpdaterTest extends TestCase {
	/** @var ServerVersion|MockObject */
	private $serverVersion;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IAppConfig|MockObject */
	private $appConfig;
	/** @var LoggerInterface|MockObject */
	private $logger;
	/** @var Updater */
	private $updater;
	/** @var Checker|MockObject */
	private $checker;
	/** @var Installer|MockObject */
	private $installer;

	protected function setUp(): void {
		parent::setUp();
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->checker = $this->createMock(Checker::class);
		$this->installer = $this->createMock(Installer::class);

		$this->updater = new Updater(
			$this->serverVersion,
			$this->config,
			$this->appConfig,
			$this->checker,
			$this->logger,
			$this->installer
		);
	}

	/**
	 * @return array
	 */
	public function versionCompatibilityTestData() {
		return [
			// Upgrade with invalid version
			['9.1.1.13', '11.0.2.25', ['nextcloud' => ['11.0' => true]], false],
			['10.0.1.13', '11.0.2.25', ['nextcloud' => ['11.0' => true]], false],
			// Upgrad with valid version
			['11.0.1.13', '11.0.2.25', ['nextcloud' => ['11.0' => true]], true],
			// Downgrade with valid version
			['11.0.2.25', '11.0.1.13', ['nextcloud' => ['11.0' => true]], false],
			['11.0.2.25', '11.0.1.13', ['nextcloud' => ['11.0' => true]], true, true],
			// Downgrade with invalid version
			['11.0.2.25', '10.0.1.13', ['nextcloud' => ['10.0' => true]], false],
			['11.0.2.25', '10.0.1.13', ['nextcloud' => ['10.0' => true]], false, true],

			// Migration with unknown vendor
			['9.1.1.13', '11.0.2.25', ['nextcloud' => ['9.1' => true]], false, false, 'owncloud'],
			['9.1.1.13', '11.0.2.25', ['nextcloud' => ['9.1' => true]], false, true, 'owncloud'],
			// Migration with unsupported vendor version
			['9.1.1.13', '11.0.2.25', ['owncloud' => ['10.0' => true]], false, false, 'owncloud'],
			['9.1.1.13', '11.0.2.25', ['owncloud' => ['10.0' => true]], false, true, 'owncloud'],
			// Migration with valid vendor version
			['9.1.1.13', '11.0.2.25', ['owncloud' => ['9.1' => true]], true, false, 'owncloud'],
			['9.1.1.13', '11.0.2.25', ['owncloud' => ['9.1' => true]], true, true, 'owncloud'],
		];
	}

	/**
	 * @dataProvider versionCompatibilityTestData
	 *
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @param array $allowedVersions
	 * @param bool $result
	 * @param bool $debug
	 * @param string $vendor
	 */
	public function testIsUpgradePossible($oldVersion, $newVersion, $allowedVersions, $result, $debug = false, $vendor = 'nextcloud'): void {
		$this->config->expects($this->any())
			->method('getSystemValueBool')
			->with('debug', false)
			->willReturn($debug);
		$this->config->expects($this->any())
			->method('getAppValue')
			->with('core', 'vendor', '')
			->willReturn($vendor);

		$this->assertSame($result, $this->updater->isUpgradePossible($oldVersion, $newVersion, $allowedVersions));
	}
}
