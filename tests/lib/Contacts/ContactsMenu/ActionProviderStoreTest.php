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

use Exception;
use OC\Contacts\ContactsMenu\ActionProviderStore;
use OC\Contacts\ContactsMenu\Providers\EMailProvider;
use OCP\AppFramework\QueryException;
use OCP\ILogger;
use OCP\IServerContainer;
use PHPUnit_Framework_MockObject_MockObject;
use Test\TestCase;

class ActionProviderStoreTest extends TestCase {

	/** @var IServerContainer|PHPUnit_Framework_MockObject_MockObject */
	private $serverContainer;

	/** @var ILogger|PHPUnit_Framework_MockObject_MockObject */
	private $logger;

	/** @var ActionProviderStore */
	private $actionProviderStore;

	protected function setUp() {
		parent::setUp();

		$this->serverContainer = $this->createMock(IServerContainer::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->actionProviderStore = new ActionProviderStore($this->serverContainer, $this->logger);
	}

	public function testGetProviders() {
		$emailProvider = $this->createMock(EMailProvider::class);
		$this->serverContainer->expects($this->exactly(2))
			->method('query')
			->will($this->returnValueMap([
					[EMailProvider::class, $emailProvider],
		]));

		$providers = $this->actionProviderStore->getProviders();

		$this->assertCount(1, $providers);
		$this->assertInstanceOf(EMailProvider::class, $providers[0]);
	}

	/**
	 * @expectedException Exception
	 */
	public function testGetProvidersWithQueryException() {
		$emailProvider = $this->createMock(EMailProvider::class);
		$detailsProvider = $this->createMock(DetailsProvider::class);
		$this->serverContainer->expects($this->once())
			->method('query')
			->willThrowException(new QueryException());

		$providers = $this->actionProviderStore->getProviders();
	}

}
