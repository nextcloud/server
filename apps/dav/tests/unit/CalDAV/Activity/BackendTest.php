<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\CalDAV\Activity;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\CalDAV\Activity\Provider\Calendar;
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

	/** @var IManager|MockObject */
	protected $activityManager;

	/** @var IGroupManager|MockObject */
	protected $groupManager;

	/** @var IUserSession|MockObject */
	protected $userSession;

	/** @var IAppManager|MockObject */
	protected $appManager;

	/** @var IUserManager|MockObject */
	protected $userManager;

	protected function setUp(): void {
		parent::setUp();
		$this->activityManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
	}

	/**
	 * @param array $methods
	 * @return Backend|MockObject
	 */
	protected function getBackend(array $methods = []) {
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

	public function dataCallTriggerCalendarActivity() {
		return [
			['onCalendarAdd', [['data']], Calendar::SUBJECT_ADD, [['data'], [], []]],
			['onCalendarUpdate', [['data'], ['shares'], ['changed-properties']], Calendar::SUBJECT_UPDATE, [['data'], ['shares'], ['changed-properties']]],
			['onCalendarDelete', [['data'], ['shares']], Calendar::SUBJECT_DELETE, [['data'], ['shares'], []]],
			['onCalendarPublication', [['data'], true], Calendar::SUBJECT_PUBLISH, [['data'], [], []]],
		];
	}

	/**
	 * @dataProvider dataCallTriggerCalendarActivity
	 *
	 * @param string $method
	 * @param array $payload
	 * @param string $expectedSubject
	 * @param array $expectedPayload
	 */
	public function testCallTriggerCalendarActivity($method, array $payload, $expectedSubject, array $expectedPayload): void {
		$backend = $this->getBackend(['triggerCalendarActivity']);
		$backend->expects($this->once())
			->method('triggerCalendarActivity')
			->willReturnCallback(function () use ($expectedPayload, $expectedSubject): void {
				$arguments = func_get_args();
				$this->assertSame($expectedSubject, array_shift($arguments));
				$this->assertEquals($expectedPayload, $arguments);
			});

		call_user_func_array([$backend, $method], $payload);
	}

	public function dataTriggerCalendarActivity() {
		return [
			// Add calendar
			[Calendar::SUBJECT_ADD, [], [], [], '', '', null, []],
			[Calendar::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], [], [], '', 'admin', null, ['admin']],
			[Calendar::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], [], [], 'test2', 'test2', null, ['admin']],

			// Update calendar
			[Calendar::SUBJECT_UPDATE, [], [], [], '', '', null, []],
			// No visible change - owner only
			[Calendar::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', null, ['admin']],
			// Visible change
			[Calendar::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], ['{DAV:}displayname' => 'Name'], '', 'admin', ['user1'], ['user1', 'admin']],
			[Calendar::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], ['{DAV:}displayname' => 'Name'], 'test2', 'test2', ['user1'], ['user1', 'admin']],

			// Delete calendar
			[Calendar::SUBJECT_DELETE, [], [], [], '', '', null, []],
			[Calendar::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', [], ['admin']],
			[Calendar::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', ['user1'], ['user1', 'admin']],
			[Calendar::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], 'test2', 'test2', ['user1'], ['user1', 'admin']],

			// Publish calendar
			[Calendar::SUBJECT_PUBLISH, [], [], [], '', '', null, []],
			[Calendar::SUBJECT_PUBLISH, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', [], ['admin']],

			// Unpublish calendar
			[Calendar::SUBJECT_UNPUBLISH, [], [], [], '', '', null, []],
			[Calendar::SUBJECT_UNPUBLISH, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'uri' => 'this-uri',
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', [], ['admin']],
		];
	}

	/**
	 * @dataProvider dataTriggerCalendarActivity
	 * @param string $action
	 * @param array $data
	 * @param array $shares
	 * @param array $changedProperties
	 * @param string $currentUser
	 * @param string $author
	 * @param string[]|null $shareUsers
	 * @param string[] $users
	 */
	public function testTriggerCalendarActivity($action, array $data, array $shares, array $changedProperties, $currentUser, $author, $shareUsers, array $users): void {
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
				->with('calendar', $data['id'])
				->willReturnSelf();
			$event->expects($this->once())
				->method('setType')
				->with('calendar')
				->willReturnSelf();
			$event->expects($this->once())
				->method('setAuthor')
				->with($author)
				->willReturnSelf();

			$this->userManager->expects($action === Calendar::SUBJECT_DELETE ? $this->exactly(sizeof($users)) : $this->never())
				->method('userExists')
				->willReturn(true);

			$event->expects($this->exactly(sizeof($users)))
				->method('setAffectedUser')
				->willReturnSelf();
			$event->expects($this->exactly(sizeof($users)))
				->method('setSubject')
				->willReturnSelf();
			$this->activityManager->expects($this->exactly(sizeof($users)))
				->method('publish')
				->with($event);
		} else {
			$this->activityManager->expects($this->never())
				->method('generateEvent');
		}

		$this->invokePrivate($backend, 'triggerCalendarActivity', [$action, $data, $shares, $changedProperties]);
	}

	public function testUserDeletionDoesNotCreateActivity(): void {
		$backend = $this->getBackend();

		$this->userManager->expects($this->once())
			->method('userExists')
			->willReturn(false);

		$this->activityManager->expects($this->never())
			->method('publish');

		$this->invokePrivate($backend, 'triggerCalendarActivity', [Calendar::SUBJECT_DELETE, [
			'principaluri' => 'principal/user/admin',
			'id' => 42,
			'uri' => 'this-uri',
			'{DAV:}displayname' => 'Name of calendar',
		], [], []]);
	}

	public function dataGetUsersForShares() {
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
	 * @param array $shares
	 * @param array $groups
	 * @param array $expected
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
	protected function getUsers(array $users) {
		$list = [];
		foreach ($users as $user) {
			$list[] = $this->getUserMock($user);
		}
		return $list;
	}

	/**
	 * @param string $uid
	 * @return IUser|MockObject
	 */
	protected function getUserMock($uid) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}
}
