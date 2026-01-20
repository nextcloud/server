<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OCA\Theming\Capabilities;
use OCA\Theming\Controller\ThemingController;
use OCA\Theming\Settings\Admin;
use OCA\Theming\Settings\PersonalSection;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\Capabilities\ICapability;
use OCP\IL10N;
use OCP\Settings\IIconSection;
use OCP\Settings\ISettings;
use Test\TestCase;

/**
 * Class ServicesTest
 *
 * @package OCA\Theming\Tests
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ServicesTest extends TestCase {
	protected App $app;

	protected IAppContainer $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new App('theming');
		$this->container = $this->app->getContainer();
	}

	public static function queryData(): array {
		return [
			[IL10N::class],

			// lib/
			[Capabilities::class],
			[Capabilities::class, ICapability::class],
			[ThemingDefaults::class],
			[ThemingDefaults::class, \OC_Defaults::class],
			[Util::class],

			// Controller
			[ThemingController::class, ThemingController::class],

			// Settings
			[Admin::class],
			[Admin::class, ISettings::class],
			[PersonalSection::class],
			[PersonalSection::class, IIconSection::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'queryData')]
	public function testContainerQuery(string $service, ?string $expected = null): void {
		if ($expected === null) {
			$expected = $service;
		}
		$this->assertInstanceOf($expected, $this->container->query($service));
	}
}
