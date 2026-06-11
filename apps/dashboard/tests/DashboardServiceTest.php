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
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;
use Test\FakeAppConfig;
use Test\FakeFrameworkAppConfig;

class DashboardServiceTest extends TestCase {

	private IUserConfig&MockObject $userConfig;
	private IAppConfig $appConfig;
	private IUserManager&MockObject $userManager;
	private IAccountManager&MockObject $accountManager;
	private DashboardService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->appConfig = new FakeFrameworkAppConfig('dashboard');
		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountManager = $this->createMock(IAccountManager::class);

		$this->service = new DashboardService(
			$this->userConfig,
			$this->appConfig,
			'alice',
			$this->userManager,
			$this->accountManager,
		);
	}

	public function testGetLayoutRemovesEmptyAndDuplicateEntries(): void {
		$this->userConfig->method('getValueString')
			->with('alice', 'dashboard', 'layout', 'recommendations,spreed,mail,calendar')
			->willReturn('spreed,,mail,mail,calendar,spreed');

		$layout = $this->service->getLayout();

		$this->assertSame(['spreed', 'mail', 'calendar'], $layout);
	}

	public function testSanitizeLayoutRemovesEmptyAndDuplicateEntries(): void {
		$layout = $this->service->sanitizeLayout(['files', 'calendar', 'files', '', 'mail', 'calendar']);

		$this->assertSame(['files', 'calendar', 'mail'], $layout);
	}

	public function testGetBirthdate(): void {
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

	public function testGetBirthdatePropertyDoesNotExist(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')
			->willReturn($user);

		$account = new Account($user);
		$this->accountManager->method('getAccount')
			->willReturn($account);

		$birthdate = $this->service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

	public function testGetBirthdateUserNotFound(): void {
		$this->userManager->method('get')
			->willReturn(null);

		$birthdate = $this->service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

	public function testGetBirthdateNoUserId(): void {
		$service = new DashboardService(
			$this->userConfig,
			$this->appConfig,
			null,
			$this->userManager,
			$this->accountManager,
		);

		$birthdate = $service->getBirthdate();

		$this->assertEquals('', $birthdate);
	}

}
