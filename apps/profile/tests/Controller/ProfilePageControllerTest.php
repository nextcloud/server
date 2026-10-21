<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profile\Tests\Controller;

use OCA\Profile\Controller\ProfilePageController;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class ProfilePageControllerTest extends TestCase {
	private ProfilePageController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->controller = $this->createInstanceWithMocks(
			ProfilePageController::class,
			[ 'appName' => 'profile'],
		);
	}

	public function testUserNotFound(): void {
		$this->getAutoMock(IUserManager::class)->method('get')
			->willReturn(null);

		$response = $this->controller->index('bob');

		$this->assertTrue($response->isThrottled());
	}

	public function testUserDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('isEnabled')
			->willReturn(false);

		$this->getAutoMock(IUserManager::class)->method('get')
			->willReturn($user);

		$response = $this->controller->index('bob');

		$this->assertFalse($response->isThrottled());
	}
}
