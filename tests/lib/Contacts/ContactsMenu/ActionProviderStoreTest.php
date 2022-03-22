<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tests\Contacts\ContactsMenu;

use OC\App\AppManager;
use OC\Contacts\ContactsMenu\ActionProviderStore;
use OC\Contacts\ContactsMenu\Providers\EMailProvider;
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

	public function testGetProviders() {
		$user = $this->createMock(IUser::class);
		$provider1 = $this->createMock(ProfileProvider::class);
		$provider2 = $this->createMock(EMailProvider::class);
		$provider3 = $this->createMock(IProvider::class);

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
		$this->serverContainer->expects($this->exactly(3))
			->method('get')
			->willReturnMap([
				[ProfileProvider::class, $provider1],
				[EMailProvider::class, $provider2],
				['OCA\Contacts\Provider1', $provider3]
			]);

		$providers = $this->actionProviderStore->getProviders($user);

		$this->assertCount(3, $providers);
		$this->assertInstanceOf(ProfileProvider::class, $providers[0]);
		$this->assertInstanceOf(EMailProvider::class, $providers[1]);
	}

	public function testGetProvidersOfAppWithIncompleInfo() {
		$user = $this->createMock(IUser::class);
		$provider1 = $this->createMock(ProfileProvider::class);
		$provider2 = $this->createMock(EMailProvider::class);

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn(['contacts']);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('contacts')
			->willReturn([/* Empty info.xml */]);
		$this->serverContainer->expects($this->exactly(2))
			->method('get')
			->willReturnMap([
				[ProfileProvider::class, $provider1],
				[EMailProvider::class, $provider2],
			]);

		$providers = $this->actionProviderStore->getProviders($user);

		$this->assertCount(2, $providers);
		$this->assertInstanceOf(ProfileProvider::class, $providers[0]);
		$this->assertInstanceOf(EMailProvider::class, $providers[1]);
	}


	public function testGetProvidersWithQueryException() {
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
