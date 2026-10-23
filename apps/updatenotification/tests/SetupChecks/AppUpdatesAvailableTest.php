<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\Tests\SetupChecks;

use OC\Installer;
use OCA\UpdateNotification\SetupChecks\AppUpdatesAvailable;
use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\SetupResult;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AppUpdatesAvailableTest extends TestCase {
	private IConfig&MockObject $config;
	private IAppManager&MockObject $appManager;
	private Installer&MockObject $installer;
	private AppUpdatesAvailable $check;

	protected function setUp(): void {
		parent::setUp();

		$l10n = $this->createMock(IL10N::class);
		$l10n->method('t')->willReturnArgument(0);
		$l10n->method('n')->willReturnCallback(
			fn (string $singular, string $plural, int $count, array $params) => vsprintf($count === 1 ? $singular : $plural, $params),
		);
		$this->config = $this->createMock(IConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->installer = $this->createMock(Installer::class);

		$this->check = new AppUpdatesAvailable(
			$l10n,
			$this->config,
			$this->createMock(IURLGenerator::class),
			$this->appManager,
			$this->installer,
		);
	}

	public function testNoInternetConnection(): void {
		$this->config->method('getSystemValueBool')->with('has_internet_connection', true)->willReturn(false);
		$this->installer->expects($this->never())->method('isUpdateAvailable');

		$this->assertSame(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testAllAppsUpToDate(): void {
		$this->config->method('getSystemValueBool')->willReturn(true);
		$this->appManager->method('getEnabledApps')->willReturn(['files', 'calendar']);
		$this->installer->method('isUpdateAvailable')->willReturn(false);

		$this->assertSame(SetupResult::SUCCESS, $this->check->run()->getSeverity());
	}

	public function testAppUpdatesAvailable(): void {
		$this->config->method('getSystemValueBool')->willReturn(true);
		$this->appManager->method('getEnabledApps')->willReturn(['files', 'calendar', 'contacts']);
		$this->installer->method('isUpdateAvailable')->willReturnMap([
			['files', false, false],
			['calendar', false, '6.0.1'],
			['contacts', false, '8.0.2'],
		]);

		$result = $this->check->run();
		$this->assertSame(SetupResult::WARNING, $result->getSeverity());
		$this->assertSame('Updates are available for the following apps: calendar, contacts', $result->getDescription());
	}
}
