<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\AccessFactory;
use OCA\User_LDAP\Helper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCA\User_LDAP\User_Proxy;
use OCA\User_LDAP\UserPluginManager;
use OCP\Notification\IManager as INotificationManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class User_ProxyTest extends TestCase {
	protected Helper&MockObject $helper;
	private ILDAPWrapper&MockObject $ldapWrapper;
	private AccessFactory&MockObject $accessFactory;
	private INotificationManager&MockObject $notificationManager;
	private User_Proxy&MockObject $proxy;
	private UserPluginManager&MockObject $userPluginManager;
	protected LoggerInterface&MockObject $logger;
	protected DeletedUsersIndex&MockObject $deletedUsersIndex;

	protected function setUp(): void {
		parent::setUp();

		$this->helper = $this->createMock(Helper::class);
		$this->ldapWrapper = $this->createMock(ILDAPWrapper::class);
		$this->accessFactory = $this->createMock(AccessFactory::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->userPluginManager = $this->createMock(UserPluginManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->deletedUsersIndex = $this->createMock(DeletedUsersIndex::class);
		$this->proxy = $this->getMockBuilder(User_Proxy::class)
			->setConstructorArgs([
				$this->helper,
				$this->ldapWrapper,
				$this->accessFactory,
				$this->notificationManager,
				$this->userPluginManager,
				$this->logger,
				$this->deletedUsersIndex,
			])
			->onlyMethods(['handleRequest'])
			->getMock();
	}

	public function testSetPassword(): void {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'setPassword', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->setPassword('MyUid', 'MyPassword'));
	}

	public function testSetDisplayName(): void {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'setDisplayName', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->setDisplayName('MyUid', 'MyPassword'));
	}

	public function testCreateUser(): void {
		$this->proxy
			->expects($this->once())
			->method('handleRequest')
			->with('MyUid', 'createUser', ['MyUid', 'MyPassword'])
			->willReturn(true);

		$this->assertTrue($this->proxy->createUser('MyUid', 'MyPassword'));
	}
}
