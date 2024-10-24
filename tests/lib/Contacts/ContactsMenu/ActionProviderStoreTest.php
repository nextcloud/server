<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Tests\Contacts\ContactsMenu;

use OC\App\AppManager;
use OC\Contacts\ContactsMenu\ActionProviderStore;
use OC\Contacts\ContactsMenu\Providers\EMailProvider;
use OC\Contacts\ContactsMenu\Providers\LocalTimeProvider;
use OC\Contacts\ContactsMenu\Providers\ProfileProvider;
use OCP\App\IAppManager;
use OCP\AppFramework\QueryException;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IServerContainer;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ActionProviderStoreTest extends TestCase {
	/** @var IServerContainer|MockObject */
	private $serverContainer;

	/** @var IAppManager|MockObject */
	private $appManager;

	private ActionProviderStore $actionProviderStore;

	protected function setUp(): void {
		parent::setUp();

		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->appManager = $this->createMock(AppManager::class);
		$logger = $this->createMock(LoggerInterface::class);

		$this->actionProviderStore = new ActionProviderStore($this->serverContainer, $this->appManager, $logger);
	}

	public function testGetProviders(): void {
		$user = $this->createMock(IUser::class);
		$provider1 = $this->createMock(ProfileProvider::class);
		$provider2 = $this->createMock(LocalTimeProvider::class);
		$provider3 = $this->createMock(EMailProvider::class);
		$provider4 = $this->createMock(IProvider::class);

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn(['contacts']);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('contacts')
			->willReturn([
				'contactsmenu' => [
					'OCA\Contacts\Provider1',
				],
			]);
		$this->serverContainer->expects($this->exactly(4))
			->method('get')
			->willReturnMap([
				[ProfileProvider::class, $provider1],
				[LocalTimeProvider::class, $provider2],
				[EMailProvider::class, $provider3],
				['OCA\Contacts\Provider1', $provider4]
			]);

		$providers = $this->actionProviderStore->getProviders($user);

		$this->assertCount(4, $providers);
		$this->assertInstanceOf(ProfileProvider::class, $providers[0]);
		$this->assertInstanceOf(LocalTimeProvider::class, $providers[1]);
		$this->assertInstanceOf(EMailProvider::class, $providers[2]);
	}

	public function testGetProvidersOfAppWithIncompleInfo(): void {
		$user = $this->createMock(IUser::class);
		$provider1 = $this->createMock(ProfileProvider::class);
		$provider2 = $this->createMock(LocalTimeProvider::class);
		$provider3 = $this->createMock(EMailProvider::class);

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn(['contacts']);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('contacts')
			->willReturn([/* Empty info.xml */]);
		$this->serverContainer->expects($this->exactly(3))
			->method('get')
			->willReturnMap([
				[ProfileProvider::class, $provider1],
				[LocalTimeProvider::class, $provider2],
				[EMailProvider::class, $provider3],
			]);

		$providers = $this->actionProviderStore->getProviders($user);

		$this->assertCount(3, $providers);
		$this->assertInstanceOf(ProfileProvider::class, $providers[0]);
		$this->assertInstanceOf(LocalTimeProvider::class, $providers[1]);
		$this->assertInstanceOf(EMailProvider::class, $providers[2]);
	}


	public function testGetProvidersWithQueryException(): void {
		$this->expectException(\Exception::class);

		$user = $this->createMock(IUser::class);
		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([]);
		$this->serverContainer->expects($this->once())
			->method('get')
			->willThrowException(new QueryException());

		$this->actionProviderStore->getProviders($user);
	}
}
