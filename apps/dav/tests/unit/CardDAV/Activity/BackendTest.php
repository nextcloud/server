<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CardDAV\Activity;

use OCA\DAV\CardDAV\Activity\Backend;
use OCA\DAV\CardDAV\Activity\Provider\Addressbook;
use OCA\DAV\CardDAV\Activity\Provider\Card;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\App\IAppManager;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BackendTest extends TestCase {
	protected IManager&MockObject $activityManager;
	protected IGroupManager&MockObject $groupManager;
	protected IUserSession&MockObject $userSession;
	protected IAppManager&MockObject $appManager;
	protected IUserManager&MockObject $userManager;

	protected function setUp(): void {
		parent::setUp();
		$this->activityManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
	}

	/**
	 * @return Backend|MockObject
	 */
	protected function getBackend(array $methods = []): Backend {
		if (empty($methods)) {
			return new Backend(
				$this->activityManager,
				$this->groupManager,
				$this->userSession,
				$this->appManager,
				$this->userManager
			);
		} else {
			return $this->getMockBuilder(Backend::class)
				->setConstructorArgs([
					$this->activityManager,
					$this->groupManager,
					$this->userSession,
					$this->appManager,
					$this->userManager
				])
				->onlyMethods($methods)
				->getMock();
		}
	}

	public static function dataCallTriggerAddressBookActivity(): array {
		return [
			['onAddressbookCreate', [['data']], Addressbook::SUBJECT_ADD, [['data'], [], []]],
			['onAddressbookUpdate', [['data'], ['shares'], ['changed-properties']], Addressbook::SUBJECT_UPDATE, [['data'], ['shares'], ['changed-properties']]],
			['onAddressbookDelete', [['data'], ['shares']], Addressbook::SUBJECT_DELETE, [['data'], ['shares'], []]],
		];
	}

	/**
	 * @dataProvider dataCallTriggerAddressBookActivity
	 */
	public function testCallTriggerAddressBookActivity(string $method, array $payload, string $expectedSubject, array $expectedPayload): void {
		$backend = $this->getBackend(['triggerAddressbookActivity']);
		$backend->expects($this->once())
			->method('triggerAddressbookActivity')
			->willReturnCallback(function () use ($expectedPayload, $expectedSubject): void {
				$arguments = func_get_args();
				$this->assertSame($expectedSubject, array_shift($arguments));
				$this->assertEquals($expectedPayload, $arguments);
			});

		call_user_func_array([$backend, $method], $payload);
	}

	public static function dataTriggerAddressBookActivity(): array {
		return [
			// Add addressbook
			[Addressbook::SUBJECT_ADD, [], [], [], '', '', null, []],
			[Addressbook::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], [], [], '', 'admin', null, ['admin']],
			[Addressbook::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], [], [], 'test2', 'test2', null, ['admin']],

			// Update addressbook
			[Addressbook::SUBJECT_UPDATE, [], [], [], '', '', null, []],
			// No visible change - owner only
			[Addressbook::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], [], '', 'admin', null, ['admin']],
			// Visible change
			[Addressbook::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['{DAV:}displayname' => 'Name'], '', 'admin', ['user1'], ['user1', 'admin']],
			[Addressbook::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['{DAV:}displayname' => 'Name'], 'test2', 'test2', ['user1'], ['user1', 'admin']],

			// Delete addressbook
			[Addressbook::SUBJECT_DELETE, [], [], [], '', '', null, []],
			[Addressbook::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], [], '', 'admin', [], ['admin']],
			[Addressbook::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], [], '', 'admin', ['user1'], ['user1', 'admin']],
			[Addressbook::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], [], 'test2', 'test2', ['user1'], ['user1', 'admin']],
		];
	}

	/**
	 * @dataProvider dataTriggerAddressBookActivity
	 * @param string[]|null $shareUsers
	 * @param string[] $users
	 */
	public function testTriggerAddressBookActivity(string $action, array $data, array $shares, array $changedProperties, string $currentUser, string $author, ?array $shareUsers, array $users): void {
		$backend = $this->getBackend(['getUsersForShares']);

		if ($shareUsers === null) {
			$backend->expects($this->never())
				->method('getUsersForShares');
		} else {
			$backend->expects($this->once())
				->method('getUsersForShares')
				->with($shares)
				->willReturn($shareUsers);
		}

		if ($author !== '') {
			if ($currentUser !== '') {
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn($this->getUserMock($currentUser));
			} else {
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn(null);
			}

			$event = $this->createMock(IEvent::class);
			$this->activityManager->expects($this->once())
				->method('generateEvent')
				->willReturn($event);

			$event->expects($this->once())
				->method('setApp')
				->with('dav')
				->willReturnSelf();
			$event->expects($this->once())
				->method('setObject')
				->with('addressbook', $data['id'])
				->willReturnSelf();
			$event->expects($this->once())
				->method('setType')
				->with('contacts')
				->willReturnSelf();
			$event->expects($this->once())
				->method('setAuthor')
				->with($author)
				->willReturnSelf();

			$this->userManager->expects($action === Addressbook::SUBJECT_DELETE ? $this->exactly(sizeof($users)) : $this->never())
				->method('userExists')
				->willReturn(true);

			$event->expects($this->exactly(count($users)))
				->method('setAffectedUser')
				->willReturnSelf();
			$event->expects($this->exactly(count($users)))
				->method('setSubject')
				->willReturnSelf();
			$this->activityManager->expects($this->exactly(count($users)))
				->method('publish')
				->with($event);
		} else {
			$this->activityManager->expects($this->never())
				->method('generateEvent');
		}

		$this->invokePrivate($backend, 'triggerAddressbookActivity', [$action, $data, $shares, $changedProperties]);
	}

	public function testNoAddressbookActivityCreatedForSystemAddressbook(): void {
		$backend = $this->getBackend();
		$this->activityManager->expects($this->never())
			->method('generateEvent');
		$this->assertEmpty($this->invokePrivate($backend, 'triggerAddressbookActivity', [Addressbook::SUBJECT_ADD, ['principaluri' => 'principals/system/system'], [], [], '', '', null, []]));
	}

	public function testUserDeletionDoesNotCreateActivity(): void {
		$backend = $this->getBackend();

		$this->userManager->expects($this->once())
			->method('userExists')
			->willReturn(false);

		$this->activityManager->expects($this->never())
			->method('publish');

		$this->invokePrivate($backend, 'triggerAddressbookActivity', [Addressbook::SUBJECT_DELETE, [
			'principaluri' => 'principal/user/admin',
			'id' => 42,
			'uri' => 'this-uri',
			'{DAV:}displayname' => 'Name of addressbook',
		], [], []]);
	}

	public static function dataTriggerCardActivity(): array {
		$cardData = "BEGIN:VCARD\r\nVERSION:3.0\r\nPRODID:-//Sabre//Sabre VObject 3.4.8//EN\r\nUID:test-user\r\nFN:test-user\r\nN:test-user;;;;\r\nEND:VCARD\r\n\r\n";

		return [
			// Add card
			[Card::SUBJECT_ADD, [], [], [], '', '', null, []],
			[Card::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], [], [
				'carddata' => $cardData
			], '', 'admin', [], ['admin']],
			[Card::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], [], ['carddata' => $cardData], 'test2', 'test2', [], ['admin']],

			// Update card
			[Card::SUBJECT_UPDATE, [], [], [], '', '', null, []],
			// No visible change - owner only
			[Card::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], '', 'admin', [], ['admin']],
			// Visible change
			[Card::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], '', 'admin', ['user1'], ['user1', 'admin']],
			[Card::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], 'test2', 'test2', ['user1'], ['user1', 'admin']],

			// Delete card
			[Card::SUBJECT_DELETE, [], [], ['carddata' => $cardData], '', '', null, []],
			[Card::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], '', 'admin', [], ['admin']],
			[Card::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], '', 'admin', ['user1'], ['user1', 'admin']],
			[Card::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of addressbook',
			], ['shares'], ['carddata' => $cardData], 'test2', 'test2', ['user1'], ['user1', 'admin']],
		];
	}

	/**
	 * @dataProvider dataTriggerCardActivity
	 * @param string[]|null $shareUsers
	 * @param string[] $users
	 */
	public function testTriggerCardActivity(string $action, array $addressBookData, array $shares, array $cardData, string $currentUser, string $author, ?array $shareUsers, array $users): void {
		$backend = $this->getBackend(['getUsersForShares']);

		if ($shareUsers === null) {
			$backend->expects($this->never())
				->method('getUsersForShares');
		} else {
			$backend->expects($this->once())
				->method('getUsersForShares')
				->with($shares)
				->willReturn($shareUsers);
		}

		if ($author !== '') {
			if ($currentUser !== '') {
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn($this->getUserMock($currentUser));
			} else {
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn(null);
			}

			$event = $this->createMock(IEvent::class);
			$this->activityManager->expects($this->once())
				->method('generateEvent')
				->willReturn($event);

			$event->expects($this->once())
				->method('setApp')
				->with('dav')
				->willReturnSelf();
			$event->expects($this->once())
				->method('setObject')
				->with('addressbook', $addressBookData['id'])
				->willReturnSelf();
			$event->expects($this->once())
				->method('setType')
				->with('contacts')
				->willReturnSelf();
			$event->expects($this->once())
				->method('setAuthor')
				->with($author)
				->willReturnSelf();

			$event->expects($this->exactly(count($users)))
				->method('setAffectedUser')
				->willReturnSelf();
			$event->expects($this->exactly(count($users)))
				->method('setSubject')
				->willReturnSelf();
			$this->activityManager->expects($this->exactly(count($users)))
				->method('publish')
				->with($event);
		} else {
			$this->activityManager->expects($this->never())
				->method('generateEvent');
		}

		$this->invokePrivate($backend, 'triggerCardActivity', [$action, $addressBookData, $shares, $cardData]);
	}

	public function testNoCardActivityCreatedForSystemAddressbook(): void {
		$backend = $this->getBackend();
		$this->activityManager->expects($this->never())
			->method('generateEvent');
		$this->assertEmpty($this->invokePrivate($backend, 'triggerCardActivity', [Card::SUBJECT_UPDATE, ['principaluri' => 'principals/system/system'], [], []]));
	}

	public static function dataGetUsersForShares(): array {
		return [
			[
				[],
				[],
				[],
			],
			[
				[
					['{http://owncloud.org/ns}principal' => 'principal/users/user1'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user3'],
				],
				[],
				['user1', 'user2', 'user3'],
			],
			[
				[
					['{http://owncloud.org/ns}principal' => 'principal/users/user1'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/groups/group2'],
					['{http://owncloud.org/ns}principal' => 'principal/groups/group3'],
				],
				['group2' => null, 'group3' => null],
				['user1', 'user2'],
			],
			[
				[
					['{http://owncloud.org/ns}principal' => 'principal/users/user1'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/users/user2'],
					['{http://owncloud.org/ns}principal' => 'principal/groups/group2'],
					['{http://owncloud.org/ns}principal' => 'principal/groups/group3'],
				],
				['group2' => ['user1', 'user2', 'user3'], 'group3' => ['user2', 'user3', 'user4']],
				['user1', 'user2', 'user3', 'user4'],
			],
		];
	}

	/**
	 * @dataProvider dataGetUsersForShares
	 */
	public function testGetUsersForShares(array $shares, array $groups, array $expected): void {
		$backend = $this->getBackend();

		$getGroups = [];
		foreach ($groups as $gid => $members) {
			if ($members === null) {
				$getGroups[] = [$gid, null];
				continue;
			}

			$group = $this->createMock(IGroup::class);
			$group->expects($this->once())
				->method('getUsers')
				->willReturn($this->getUsers($members));

			$getGroups[] = [$gid, $group];
		}

		$this->groupManager->expects($this->exactly(sizeof($getGroups)))
			->method('get')
			->willReturnMap($getGroups);

		$users = $this->invokePrivate($backend, 'getUsersForShares', [$shares]);
		sort($users);
		$this->assertEquals($expected, $users);
	}

	/**
	 * @param string[] $users
	 * @return IUser[]|MockObject[]
	 */
	protected function getUsers(array $users): array {
		$list = [];
		foreach ($users as $user) {
			$list[] = $this->getUserMock($user);
		}
		return $list;
	}

	/**
	 * @return IUser|MockObject
	 */
	protected function getUserMock(string $uid): IUser {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}
}
