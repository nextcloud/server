<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\SetupChecks;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\ConfigLexicon;
use OCA\DAV\SetupChecks\SystemAddressBookSize;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IUserManager;
use PHPUnit\Framework\TestCase;

class SystemAddressBookSizeTest extends TestCase {
	private $appConfig;
	private $userManager;
	private $l10n;

	protected function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnCallback(fn ($text, $parameters = []) => vsprintf($text, $parameters));
	}

	public function testSystemAddressBookDisabled() {
		$this->appConfig->method('getValueBool')
			->with(Application::APP_ID, ConfigLexicon::SYSTEM_ADDRESSBOOK_EXPOSED)
			->willReturn(false);

		$check = new SystemAddressBookSize($this->appConfig, $this->userManager, $this->l10n);
		$result = $check->run();
		$this->assertEquals('success', $result->getSeverity());
		$this->assertStringContainsString('disabled', $result->getDescription());
	}

	public function testSystemAddressBookOverLimit() {
		$this->appConfig->method('getValueBool')
			->willReturn(true);
		$this->userManager->method('countSeenUsers')
			->willReturn(6000);
		$this->appConfig->method('getValueInt')
			->willReturn(5000);

		$check = new SystemAddressBookSize($this->appConfig, $this->userManager, $this->l10n);
		$result = $check->run();
		$this->assertEquals('warning', $result->getSeverity());
		$this->assertStringContainsString('more than the configured limit', $result->getDescription());
	}

	public function testSystemAddressBookUnderLimit() {
		$this->appConfig->method('getValueBool')
			->willReturn(true);
		$this->userManager->method('countSeenUsers')
			->willReturn(1000);
		$this->appConfig->method('getValueInt')
			->willReturn(5000);

		$check = new SystemAddressBookSize($this->appConfig, $this->userManager, $this->l10n);
		$result = $check->run();
		$this->assertEquals('success', $result->getSeverity());
		$this->assertStringContainsString('less than the configured limit', $result->getDescription());
	}
}
