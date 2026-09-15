<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Tests;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\Db\GroupMembershipMapper;
use OCA\User_LDAP\Group_Proxy;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LoginListener;
use OCA\User_LDAP\User_Proxy;
use OCP\Config\IUserConfig;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class LoginListenerTest extends \Test\TestCase {
	private IEventDispatcher&MockObject $dispatcher;
	private Group_Proxy&MockObject $groupBackend;
	private IGroupManager&MockObject $groupManager;
	private User_Proxy&MockObject $userBackend;
	private LoggerInterface&MockObject $logger;
	private GroupMembershipMapper&MockObject $groupMembershipMapper;
	private IUserConfig&MockObject $userConfig;
	private INotificationManager&MockObject $notificationManager;

	private LoginListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->groupBackend = $this->createMock(Group_Proxy::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userBackend = $this->createMock(User_Proxy::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->groupMembershipMapper = $this->createMock(GroupMembershipMapper::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);

		$this->listener = new LoginListener(
			$this->dispatcher,
			$this->groupBackend,
			$this->groupManager,
			$this->userBackend,
			$this->logger,
			$this->groupMembershipMapper,
			$this->userConfig,
			$this->notificationManager,
		);
	}

	public static function dataProviderCustomPolicy(): array {
		return [
			[null],
			['cn=custom,ou=policies,dc=foo,dc=bar'],
		];
	}

	#[DataProvider(methodName: 'dataProviderCustomPolicy')]
	public function testHandlePasswordExpiryWarning(?string $customDn): void {
		$uid = 'alice';
		$dn = 'uid=alice,dc=foo,dc=bar';
		// Cannot use createMock because of the __destruct in Connection
		$connection = $this->getMockBuilder(Connection::class)
			->setConstructorArgs([$this->createMock(ILDAPWrapper::class)])
			->getMock();
		$connection->expects(self::any())
			->method('__get')
			->willReturnCallback(function ($name) {
				if ($name === 'ldapDefaultPPolicyDN') {
					return 'cn=default,ou=policies,dc=foo,dc=bar';
				}
				if ($name === 'turnOnPasswordChange') {
					return '1';
				}
				return $name;
			});

		$access = $this->createMock(Access::class);
		$access->expects(self::atLeastOnce())
			->method('getConnection')
			->willReturn($connection);
		$access->expects(self::any())
			->method('search')
			->willReturnCallback(static function (string $filter, string $base) use ($dn, $customDn) {
				if ($base === $dn) {
					$attrs = [
						[
							'pwdchangedtime' => [(new \DateTime())->sub(new \DateInterval('P28D'))->format('Ymdhis') . 'Z'],
							'pwdgraceusetime' => [],
						],
					];
					if ($customDn !== null) {
						$attrs[0]['pwdpolicysubentry'] = [$customDn];
					}
					return $attrs;
				}
				if ($base === ($customDn ?? 'cn=default,ou=policies,dc=foo,dc=bar')) {
					return [
						[
							'pwdmaxage' => ['2592000'],
							'pwdexpirewarning' => ['2591999'],
						],
					];
				}
				throw new \Error('Should not happen');
			});
		$access->method('username2dn')->with($uid)->willReturn($dn);
		$this->userBackend->expects(self::once())
			->method('getLDAPAccess')
			->willReturn($access);

		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->any())
			->method('setApp')
			->willReturn($notification);
		$notification->expects($this->any())
			->method('setUser')
			->willReturn($notification);
		$notification->expects($this->any())
			->method('setObject')
			->willReturn($notification);
		$notification->expects($this->any())
			->method('setDateTime')
			->willReturn($notification);

		$this->notificationManager->expects($this->exactly(2))
			->method('createNotification')
			->willReturn($notification);
		$this->notificationManager->expects($this->exactly(1))
			->method('notify');

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);

		self::invokePrivate($this->listener, 'handlePasswordExpiry', [$user]);
	}
}
