<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\AppInfo;

use OCA\Settings\AppInfo\Application;
use OCA\Settings\Controller\AdminSettingsController;
use OCA\Settings\Controller\AppSettingsController;
use OCA\Settings\Controller\AuthSettingsController;
use OCA\Settings\Controller\CheckSetupController;
use OCA\Settings\Controller\LogSettingsController;
use OCA\Settings\Controller\MailSettingsController;
use OCA\Settings\Controller\UsersController;
use OCA\Settings\Middleware\SubadminMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\IAppContainer;
use OCP\AppFramework\Middleware;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @package Tests\Settings
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class ApplicationTest extends TestCase {
	protected Application $app;
	protected IAppContainer $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName(): void {
		$this->app = new Application();
		$this->assertEquals('settings', $this->container->get('appName'));
	}

	public static function dataContainerQuery(): array {
		return [
			[AdminSettingsController::class, Controller::class],
			[AppSettingsController::class, Controller::class],
			[AuthSettingsController::class, Controller::class],
			[CheckSetupController::class, Controller::class],
			[LogSettingsController::class, Controller::class],
			[MailSettingsController::class, Controller::class],
			[UsersController::class, Controller::class],

			[SubadminMiddleware::class, Middleware::class],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataContainerQuery')]
	public function testContainerQuery(string $service, string $expected): void {
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}
}
