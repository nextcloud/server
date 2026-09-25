<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\Installer;
use OC\Updater;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\IConfig;

class UpdaterTest extends TestCase {
	/** @var Updater */
	private $updater;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->updater = $this->createInstanceWithMocks(Updater::class);
	}

	/**
	 * @return array
	 */
	public static function versionCompatibilityTestData(): array {
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
	 *
	 * @param string $oldVersion
	 * @param string $newVersion
	 * @param array $allowedVersions
	 * @param bool $result
	 * @param bool $debug
	 * @param string $vendor
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('versionCompatibilityTestData')]
	public function testIsUpgradePossible($oldVersion, $newVersion, $allowedVersions, $result, $debug = false, $vendor = 'nextcloud'): void {
		$this->mocks[IConfig::class]->expects($this->any())
			->method('getSystemValueBool')
			->with('debug', false)
			->willReturn($debug);
		$this->mocks[IConfig::class]->expects($this->any())
			->method('getAppValue')
			->with('core', 'vendor', '')
			->willReturn($vendor);

		$this->assertSame($result, $this->updater->isUpgradePossible($oldVersion, $newVersion, $allowedVersions));
	}

	/**
	 * @return array
	 */
	public static function majorUpgradeTestData(): array {
		return [
			// Same major version
			['33.0.0.10', '33.1.2.3', false],
			// Major upgrade
			['33.0.5.1', '34.0.0.10', true],
			// Downgrade, only reachable with debug enabled
			['34.0.0.10', '33.0.5.1', false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('majorUpgradeTestData')]
	public function testIsMajorUpgrade(string $installedVersion, string $currentVersion, bool $result): void {
		$this->assertSame($result, self::invokePrivate($this->updater, 'isMajorUpgrade', [$installedVersion, $currentVersion]));
	}

	public function testUpgradeAppStoreAppsRestoresMissingAutoDisabledAppBeforeEnabling(): void {
		$this->mocks[Installer::class]->expects($this->once())
			->method('isUpdateAvailable')
			->with('mailroundcube')
			->willReturn(false);

		$this->mocks[Installer::class]->expects($this->once())
			->method('downloadApp')
			->with('mailroundcube');

		$this->mocks[Installer::class]->expects($this->once())
			->method('installApp')
			->with('mailroundcube');

		$this->mocks[IAppManager::class]->expects($this->once())
			->method('getAppPath')
			->with('mailroundcube', true)
			->willThrowException(new AppPathNotFoundException('missing'));

		$this->mocks[IAppManager::class]->expects($this->once())
			->method('enableApp')
			->with('mailroundcube');

		$this->mocks[IAppManager::class]->expects($this->never())
			->method('enableAppForGroups');

		self::invokePrivate($this->updater, 'upgradeAppStoreApps', [
			['mailroundcube'],
			['mailroundcube' => 'yes'],
		]);
	}
}
