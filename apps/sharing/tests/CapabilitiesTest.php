<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Tests;

use NCU\Sharing\ISharingRegistry;
use OCA\Sharing\AppInfo\Application;
use OCA\Sharing\Capabilities;
use OCP\IConfig;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\Sharing\TestSharePermissionPreset1;
use Test\Sharing\TestSharePermissionPreset2;
use Test\Sharing\TestShareSourceType1;
use Test\Sharing\TestShareSourceType2;
use Test\TestCase;

#[Group(name: 'DB')]
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
		$config = Server::get(IConfig::class);
		$config->setSystemValue('sharing.unified_api_enable', true);

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

		$config->deleteSystemValue('sharing.unified_api_enable');
	}

	public function testGetCapabilitiesDisableUnifiedSharingApi(): void {
		$config = Server::get(IConfig::class);
		$config->setSystemValue('sharing.unified_api_enable', false);

		$this->assertEquals([], $this->capabilities->getCapabilities());

		$config->deleteSystemValue('sharing.unified_api_enable');
	}
}
