<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\User\Manager;
use OCA\User_LDAP\User\User;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Image;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class Test_User_Manager
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\User
 */
class ManagerTest extends \Test\TestCase {
	protected Access&MockObject $access;
	protected IConfig&MockObject $config;
	protected LoggerInterface&MockObject $logger;
	protected IAvatarManager&MockObject $avatarManager;
	protected Image&MockObject $image;
	protected IDBConnection&MockObject $dbc;
	protected IUserManager&MockObject $ncUserManager;
	protected INotificationManager&MockObject $notificationManager;
	protected ILDAPWrapper&MockObject $ldapWrapper;
	protected Connection $connection;
	protected IManager&MockObject $shareManager;
	protected Manager $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->access = $this->createMock(Access::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->image = $this->createMock(Image::class);
		$this->ncUserManager = $this->createMock(IUserManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->ldapWrapper = $this->createMock(ILDAPWrapper::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->connection = new Connection($this->ldapWrapper, '', null);

		$this->access->expects($this->any())
			->method('getConnection')
			->willReturn($this->connection);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->manager = new Manager(
			$this->config,
			$this->logger,
			$this->avatarManager,
			$this->image,
			$this->ncUserManager,
			$this->notificationManager,
			$this->shareManager
		);

		$this->manager->setLdapAccess($this->access);
	}

	public static function dnProvider(): array {
		return [
			['cn=foo,dc=foobar,dc=bar'],
			['uid=foo,o=foobar,c=bar'],
			['ab=cde,f=ghei,mno=pq'],
		];
	}

	/**
	 * @dataProvider dnProvider
	 */
	public function testGetByDNExisting(string $inputDN): void {
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$this->access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->willReturn(true);
		$this->access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->willReturn($uid);
		$this->access->expects($this->never())
			->method('username2dn');

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->manager->get($inputDN);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		/** @noinspection PhpUnhandledExceptionInspection */
		$user = $this->manager->get($inputDN);

		$this->assertInstanceOf(User::class, $user);
	}

	public function testGetByDNNotExisting(): void {
		$inputDN = 'cn=gone,dc=foobar,dc=bar';

		$this->access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->willReturn(true);
		$this->access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->willReturn(false);
		$this->access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($inputDN))
			->willReturn(false);

		/** @noinspection PhpUnhandledExceptionInspection */
		$user = $this->manager->get($inputDN);

		$this->assertNull($user);
	}

	public function testGetByUidExisting(): void {
		$dn = 'cn=foo,dc=foobar,dc=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$this->access->expects($this->never())
			->method('dn2username');
		$this->access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($uid))
			->willReturn($dn);
		$this->access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($uid))
			->willReturn(false);

		/** @noinspection PhpUnhandledExceptionInspection */
		$this->manager->get($uid);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		/** @noinspection PhpUnhandledExceptionInspection */
		$user = $this->manager->get($uid);

		$this->assertInstanceOf(User::class, $user);
	}

	public function testGetByUidNotExisting(): void {
		$uid = 'gone';

		$this->access->expects($this->never())
			->method('dn2username');
		$this->access->expects($this->exactly(1))
			->method('username2dn')
			->with($this->equalTo($uid))
			->willReturn(false);

		/** @noinspection PhpUnhandledExceptionInspection */
		$user = $this->manager->get($uid);

		$this->assertNull($user);
	}

	public static function attributeRequestProvider(): array {
		return [
			[false],
			[true],
		];
	}

	/**
	 * @dataProvider attributeRequestProvider
	 */
	public function testGetAttributes($minimal): void {
		$this->connection->setConfiguration([
			'ldapEmailAttribute' => 'MAIL',
			'ldapUserAvatarRule' => 'default',
			'ldapQuotaAttribute' => '',
			'ldapUserDisplayName2' => 'Mail',
		]);

		$attributes = $this->manager->getAttributes($minimal);

		$this->assertContains('dn', $attributes);
		$this->assertContains(strtolower($this->access->getConnection()->ldapEmailAttribute), $attributes);
		$this->assertNotContains($this->access->getConnection()->ldapEmailAttribute, $attributes); #cases check
		$this->assertNotContains('', $attributes);
		$this->assertSame(!$minimal, in_array('jpegphoto', $attributes));
		$this->assertSame(!$minimal, in_array('thumbnailphoto', $attributes));
		$valueCounts = array_count_values($attributes);
		$this->assertSame(1, $valueCounts['mail']);
	}
}
