<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Mapping\UserMapping;
use OCA\User_LDAP\User\DeletedUsersIndex;
use OCP\Config\IUserConfig;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Share\IManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DeletedUsersIndexTest
 *
 *
 * @package OCA\User_LDAP\Tests\User
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class DeletedUsersIndexTest extends \Test\TestCase {
	protected DeletedUsersIndex $dui;
	protected IUserConfig $userConfig;
	protected IDBConnection $db;
	protected UserMapping&MockObject $mapping;
	protected IManager&MockObject $shareManager;

	protected function setUp(): void {
		parent::setUp();

		// no mocks for those as tests go against DB
		$this->userConfig = Server::get(IUserConfig::class);
		$this->db = Server::get(IDBConnection::class);

		// ensure a clean database
		$this->userConfig->deleteApp('user_ldap');

		$this->mapping = $this->createMock(UserMapping::class);
		$this->shareManager = $this->createMock(IManager::class);

		$this->dui = new DeletedUsersIndex($this->userConfig, $this->mapping, $this->shareManager);
	}

	protected function tearDown(): void {
		$this->userConfig->deleteApp('user_ldap');
		parent::tearDown();
	}

	public function testMarkAndFetchUser(): void {
		$uids = [
			'cef3775c-71d2-48eb-8984-39a4051b0b95',
			'8c4bbb40-33ed-42d0-9b14-85b0ab76c1cc',
		];

		// ensure test works on a pristine state
		$this->assertFalse($this->dui->hasUsers());

		$this->dui->markUser($uids[0]);

		$this->assertTrue($this->dui->hasUsers());

		$this->dui->markUser($uids[1]);

		$deletedUsers = $this->dui->getUsers();
		$this->assertSame(2, count($deletedUsers));

		// ensure the different uids were used
		foreach ($deletedUsers as $deletedUser) {
			$this->assertTrue(in_array($deletedUser->getOCName(), $uids));
			$i = array_search($deletedUser->getOCName(), $uids);
			$this->assertNotFalse($i);
			unset($uids[$i]);
		}
		$this->assertEmpty($uids);
	}

	public function testUnmarkUser(): void {
		$uids = [
			'22a162c7-a9ee-487c-9f33-0563795583fb',
			'1fb4e0da-4a75-47f3-8fa7-becc7e35c9c5',
		];

		// we know this works, because of "testMarkAndFetchUser"
		$this->dui->markUser($uids[0]);
		// this returns a working instance of OfflineUser
		$testUser = $this->dui->getUsers()[0];
		$testUser->unmark();

		// the DUI caches the users, to clear mark someone else
		$this->dui->markUser($uids[1]);

		$deletedUsers = $this->dui->getUsers();
		foreach ($deletedUsers as $deletedUser) {
			$this->assertNotSame($testUser->getOCName(), $deletedUser->getOCName());
		}
	}
}
