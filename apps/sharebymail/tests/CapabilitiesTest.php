<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ShareByMail\Tests;

use OCA\ShareByMail\Capabilities;
use OCA\ShareByMail\Settings\SettingsManager;
use OCP\App\IAppManager;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CapabilitiesTest extends TestCase {
	private IManager&MockObject $manager;
	private SettingsManager&MockObject $settingsManager;
	private IAppManager&MockObject $appManager;
	private Capabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this::createMock(IManager::class);
		$this->settingsManager = $this::createMock(SettingsManager::class);
		$this->appManager = $this::createMock(IAppManager::class);
		$this->capabilities = new Capabilities($this->manager, $this->settingsManager, $this->appManager);
	}

	public function testGetCapabilities(): void {
		$this->manager->method('shareApiAllowLinks')
			->willReturn(true);
		$this->manager->method('shareApiLinkEnforcePassword')
			->willReturn(false);
		$this->manager->method('shareApiLinkDefaultExpireDateEnforced')
			->willReturn(false);
		$this->settingsManager->method('sendPasswordByMail')
			->willReturn(true);
		$this->appManager->method('isEnabledForUser')
			->willReturn(true);

		$capabilities = [
			'files_sharing'
				=> [
					'sharebymail' => [
						'enabled' => true,
						'send_password_by_mail' => true,
						'upload_files_drop' => [
							'enabled' => true,
						],
						'password' => [
							'enabled' => true,
							'enforced' => false,
						],
						'expire_date' => [
							'enabled' => true,
							'enforced' => false,
						],
					]
				]
		];

		$this->assertSame($capabilities, $this->capabilities->getCapabilities());
	}
}
