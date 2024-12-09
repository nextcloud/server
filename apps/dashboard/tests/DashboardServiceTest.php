<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Dashboard\Tests;

use OC\Accounts\Account;
use OCA\Dashboard\Service\DashboardService;
use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class DashboardServiceTest extends TestCase {

	private IConfig&MockObject $config;
	private IUserManager&MockObject $userManager;
	private IAccountManager&MockObject $accountManager;
	private DashboardService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountManager = $this->createMock(IAccountManager::class);

		$this->service = new DashboardService(
			$this->config,
			'alice',
			$this->userManager,
			$this->accountManager,
		);
	}

	public function testGetBirthdate() {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')
			->willReturn($user);

		$account = new Account($user);
		$account->setProperty(
			IAccountManager::PROPERTY_BIRTHDATE,
			'2024-12-10T00:00:00.000Z',
			IAccountManager::SCOPE_LOCAL,
			IAccountManager::VERIFIED,
		);

		$this->accountManager->method('getAccount')
			->willReturn($account);

		$birthdate = $this->service->getBirthdate();

		$this->assertEquals('2024-12-10T00:00:00.000Z', $birthdate);
	}

	public function testGetBirthdatePropertyDoesNotExist() {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')
			->willReturn($user);

		$account = new Account($user);
		$this->accountManager->method('getAccount')
			->willReturn($account);

		$birthdate = $this->service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

	public function testGetBirthdateUserNotFound() {
		$this->userManager->method('get')
			->willReturn(null);

		$birthdate = $this->service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

	public function testGetBirthdateNoUserId() {
		$service = new DashboardService(
			$this->config,
			null,
			$this->userManager,
			$this->accountManager,
		);

		$birthdate = $service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

}
