<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Vinicius Cubas Brand <vinicius@eita.org.br>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\IConfig;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class User_ProxyTest extends TestCase {
	/** @var Helper|MockObject */
	protected $helper;
	/** @var ILDAPWrapper|MockObject */
	private $ldapWrapper;
	/** @var AccessFactory|MockObject */
	private $accessFactory;
	/** @var IConfig|MockObject */
	private $config;
	/** @var INotificationManager|MockObject */
	private $notificationManager;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var User_Proxy|MockObject */
	private $proxy;
	/** @var UserPluginManager|MockObject */
	private $userPluginManager;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var DeletedUsersIndex|MockObject */
	protected $deletedUsersIndex;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = $this->createMock(Helper::class);
		$this->ldapWrapper = $this->createMock(ILDAPWrapper::class);
		$this->accessFactory = $this->createMock(AccessFactory::class);
		$this->config = $this->createMock(IConfig::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->userPluginManager = $this->createMock(UserPluginManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->deletedUsersIndex = $this->createMock(DeletedUsersIndex::class);
		$this->proxy = $this->getMockBuilder(User_Proxy::class)
			->setConstructorArgs([
				$this->helper,
				$this->ldapWrapper,
				$this->accessFactory,
				$this->config,
				$this->notificationManager,
				$this->userSession,
				$this->userPluginManager,
				$this->logger,
				$this->deletedUsersIndex,
			])
			->setMethods(['handleRequest'])
			->getMock();
	}

	public function testSetPassword() {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'setPassword', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->setPassword('MyUid', 'MyPassword'));
	}

	public function testSetDisplayName() {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'setDisplayName', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->setDisplayName('MyUid', 'MyPassword'));
	}

	public function testCreateUser() {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'createUser', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->createUser('MyUid', 'MyPassword'));
	}
}
