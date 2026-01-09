<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Profile\Tests\Controller;

use OC\Profile\ProfileManager;
use OC\UserStatus\Manager;
use OCA\Profile\Controller\ProfilePageController;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ProfilePageControllerTest extends TestCase {

	private IUserManager&MockObject $userManager;
	private ProfilePageController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$initialStateService = $this->createMock(IInitialState::class);
		$profileManager = $this->createMock(ProfileManager::class);
		$shareManager = $this->createMock(IManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$userSession = $this->createMock(IUserSession::class);
		$userStatusManager = $this->createMock(Manager::class);
		$navigationManager = $this->createMock(INavigationManager::class);
		$eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->controller = new ProfilePageController(
			'profile',
			$request,
			$initialStateService,
			$profileManager,
			$shareManager,
			$this->userManager,
			$userSession,
			$userStatusManager,
			$navigationManager,
			$eventDispatcher,
		);
	}

	public function testUserNotFound(): void {
		$this->userManager->method('get')
			->willReturn(null);

		$response = $this->controller->index('bob');

		$this->assertTrue($response->isThrottled());
	}

	public function testUserDisabled(): void {
		$user = $this->createMock(IUser::class);
		$user->method('isEnabled')
			->willReturn(false);

		$this->userManager->method('get')
			->willReturn($user);

		$response = $this->controller->index('bob');

		$this->assertFalse($response->isThrottled());
	}
}
