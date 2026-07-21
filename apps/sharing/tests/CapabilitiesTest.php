<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

use OCA\Sharing\AppInfo\Application;
use OCA\Sharing\Capabilities;
use OCP\Server;
use OCP\Sharing\ISharingRegistry;
use Test\Sharing\TestSharePermissionPreset1;
use Test\Sharing\TestSharePermissionPreset2;
use Test\Sharing\TestShareSourceType1;
use Test\Sharing\TestShareSourceType2;
use Test\TestCase;

final class CapabilitiesTest extends TestCase {
	private ISharingRegistry $registry;

	private Capabilities $capabilities;

	#[\Override]
	public function setUp(): void {
		parent::setUp();

		$this->registry = Server::get(ISharingRegistry::class);
		$this->registry->clear();

		$this->capabilities = Server::get(Capabilities::class);
	}

	#[\Override]
	protected function tearDown(): void {
		$this->registry->clear();

		parent::tearDown();
	}

	public function testGetCapabilities(): void {
		$this->registry->registerSourceType(new TestShareSourceType1([]));
		$this->registry->registerSourceType(new TestShareSourceType2([]));
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset1());
		$this->registry->registerPermissionPreset(new TestSharePermissionPreset2());

		$this->assertEquals(
			[
				Application::APP_ID => [
					'api_versions' => ['v1'],
					'source_types' => [
						[
							'class' => TestShareSourceType1::class,
						],
						[
							'class' => TestShareSourceType2::class,
						],
					],
					'permission_presets' => [
						[
							'class' => TestSharePermissionPreset1::class,
							'display_name' => 'TestSharePermissionPreset1',
							'hint' => 'hint TestSharePermissionPreset1',
						],
						[
							'class' => TestSharePermissionPreset2::class,
							'display_name' => 'TestSharePermissionPreset2',
							'hint' => 'hint TestSharePermissionPreset2',
						],
					],
				],
			],
			$this->capabilities->getCapabilities(),
		);
	}
}
