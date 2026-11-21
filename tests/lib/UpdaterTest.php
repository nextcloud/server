<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\App\DependencyAnalyzer;
use OC\App\Platform;
use OC\Core\Command\Upgrade;
use OC\Core\Controller\UpdateController;
use OC\Installer;
use OC\Updater;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IEventSource;
use OCP\IEventSourceFactory;
use OCP\ServerVersion;
use OCP\Util;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\BufferedOutput;

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
		$this->getAutoMock(IConfig::class)->expects($this->any())
			->method('getSystemValueBool')
			->with('debug', false)
			->willReturn($debug);
		$this->getAutoMock(IConfig::class)->expects($this->any())
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

	public static function incompatibleAppMessages(): array {
		return [
			'nextcloud range' => [
				['version' => '2.3', 'dependencies' => ['nextcloud' => ['@attributes' => ['min-version' => '29', 'max-version' => '30']]]],
				'2.3', '29 to 30',
			],
			'legacy range' => [['version' => '1.0', 'requiremin' => '28', 'requiremax' => '30'], '1.0', '28 to 30'],
			'owncloud minimum' => [['dependencies' => ['owncloud' => ['@attributes' => ['min-version' => '32']]]], 'unknown', '32 or later'],
			'maximum only' => [['version' => '3', 'requiremax' => '30'], '3', '30 or earlier'],
			'missing metadata' => [null, 'unknown', 'unknown'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('incompatibleAppMessages')]
	public function testIncompatibleAppMessage(?array $info, string $version, string $requirements): void {
		$analyzer = new DependencyAnalyzer($this->createMock(Platform::class));
		$command = $this->createInstanceWithMocks(Upgrade::class, [DependencyAnalyzer::class => $analyzer]);
		$output = new BufferedOutput();

		self::invokePrivate($command, 'reportIncompatibleApp', [$output, 'deck', $info, '31.0.14.1']);

		$this->assertSame(
			"Disabled app deck $version for Nextcloud 31.0.14.1 (declared compatibility: $requirements). A compatible update will be checked later in the upgrade.\n",
			$output->fetch(),
		);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('incompatibleAppMessages')]
	public function testDisabledAppEventIncludesCheckedMetadata(?array $info, string $version, string $requirements): void {
		$this->getAutoMock(ServerVersion::class)->method('getChannel')->willReturn('stable');
		$this->getAutoMock(IConfig::class)->method('getSystemValueString')->willReturn('0.0.0');
		$appManager = $this->getAutoMock(IAppManager::class);
		$appManager->method('getEnabledApps')->willReturn(['deck']);
		$appManager->method('getAppInfo')->with('deck')->willReturn($info);
		if ($info !== null) {
			$appManager->method('isAppCompatible')->willReturn(false);
		}
		$appManager->method('isShipped')->willReturn(false);
		$appManager->expects($this->once())->method('disableApp')->with('deck', true);

		$events = [];
		$this->updater->listen('\OC\Updater', 'incompatibleAppDisabled', function (...$arguments) use (&$events): void {
			$events[] = $arguments;
		});
		self::invokePrivate($this->updater, 'checkAppsRequirements', []);

		$this->assertSame([['deck', $info, implode('.', Util::getVersion())]], $events);
	}

	public static function webDisabledAppCases(): array {
		return [
			're-enabled app' => [true, [], false, ['version' => '2.3', 'requiremin' => '29', 'requiremax' => '30'], '2.3', '29 to 30'],
			'app remains disabled' => [false, [], true, ['version' => '2.3', 'requiremin' => '29', 'requiremax' => '30'], '2.3', '29 to 30'],
			'overridden app' => [false, ['deck'], false, ['version' => '2.3'], '2.3', 'unknown'],
			'minimum only' => [false, [], true, ['requiremin' => '32'], 'unknown', '32 or later'],
			'maximum only' => [false, [], true, ['requiremax' => '30'], 'unknown', '30 or earlier'],
			'missing metadata' => [false, [], true, null, 'unknown', 'unknown'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('webDisabledAppCases')]
	public function testWebUpdaterReportsFinalAppState(bool $enabled, array $overwrites, bool $expectFinalNotice, ?array $info, string $version, string $requirements): void {
		$updater = $this->getMockBuilder(Updater::class)
			->disableOriginalConstructor()
			->onlyMethods(['upgrade'])
			->getMock();
		$updater->method('upgrade')->willReturnCallback(function () use ($updater, $info): bool {
			self::invokePrivate($updater, 'emit', ['\OC\Updater', 'incompatibleAppDisabled', ['deck', $info, '31.0.14.1']]);
			return true;
		});
		$this->getAutoMock(IConfig::class)->method('getSystemValue')->willReturn($overwrites);
		$this->getAutoMock(IAppManager::class)->method('isEnabledForAnyone')->willReturn($enabled);
		$messages = [];
		$eventSource = $this->createMock(IEventSource::class);
		$eventSource->method('send')->willReturnCallback(function (string $type, mixed $message) use (&$messages): void {
			$messages[] = [$type, $message];
		});
		$this->getAutoMock(IEventSourceFactory::class)->method('create')->willReturn($eventSource);
		$analyzer = new DependencyAnalyzer($this->createMock(Platform::class));
		$controller = $this->createInstanceWithMocks(UpdateController::class, [
			'appName' => 'core',
			Updater::class => $updater,
			DependencyAnalyzer::class => $analyzer,
		]);

		$cache = new \ReflectionProperty(Util::class, 'needUpgradeCache');
		$previous = $cache->getValue();
		$cache->setValue(null, true);
		try {
			$controller->update();
		} finally {
			$cache->setValue(null, $previous);
		}

		$progress = ['notice', "Disabled app deck $version for Nextcloud 31.0.14.1 (declared compatibility: $requirements). A compatible update will be checked later in the upgrade."];
		$final = ['notice', 'The following apps have been disabled: deck'];
		$this->assertSame($overwrites === [], in_array($progress, $messages, true));
		$this->assertSame($expectFinalNotice, in_array($final, $messages, true));
		$this->assertContains(['done', ''], $messages);
	}

	public function testDisabledAppLogIncludesCompatibilityDetails(): void {
		$this->overwriteService(IEventDispatcher::class, $this->getAutoMock(IEventDispatcher::class));
		$info = ['version' => '2.3', 'requiremin' => '29', 'requiremax' => '30'];
		$this->getAutoMock(DependencyAnalyzer::class)->method('getServerVersionRequirements')
			->with($info)->willReturn(['29', '30']);
		$this->getAutoMock(LoggerInterface::class)->expects($this->once())->method('info')
			->with(
				'\OC\Updater::incompatibleAppDisabled: Disabled app {appId} {appVersion} for Nextcloud {serverVersion} (declared minimum: {minServerVersion}, maximum: {maxServerVersion}); a compatible update will be checked later in the upgrade',
				['app' => 'updater', 'appId' => 'deck', 'appVersion' => '2.3', 'serverVersion' => '31.0.14.1', 'minServerVersion' => '29', 'maxServerVersion' => '30'],
			);
		self::invokePrivate($this->updater, 'logAllEvents', []);
		self::invokePrivate($this->updater, 'emit', ['\OC\Updater', 'incompatibleAppDisabled', ['deck', $info, '31.0.14.1']]);
	}

	public function testUpgradeAppStoreAppsRestoresMissingAutoDisabledAppBeforeEnabling(): void {
		$this->getAutoMock(Installer::class)->expects($this->once())
			->method('isUpdateAvailable')
			->with('mailroundcube')
			->willReturn(false);

		$this->getAutoMock(Installer::class)->expects($this->once())
			->method('downloadApp')
			->with('mailroundcube');

		$this->getAutoMock(Installer::class)->expects($this->once())
			->method('installApp')
			->with('mailroundcube');

		$this->getAutoMock(IAppManager::class)->expects($this->once())
			->method('getAppPath')
			->with('mailroundcube', true)
			->willThrowException(new AppPathNotFoundException('missing'));

		$this->getAutoMock(IAppManager::class)->expects($this->once())
			->method('enableApp')
			->with('mailroundcube');

		$this->getAutoMock(IAppManager::class)->expects($this->never())
			->method('enableAppForGroups');

		self::invokePrivate($this->updater, 'upgradeAppStoreApps', [
			['mailroundcube'],
			['mailroundcube' => 'yes'],
		]);
	}
}
