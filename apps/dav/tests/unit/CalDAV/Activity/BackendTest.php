<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\DAV\Tests\unit\CalDAV\Activity;

use OCA\DAV\CalDAV\Activity\Backend;
use OCA\DAV\CalDAV\Activity\Extension;
use OCP\Activity\IEvent;
use OCP\Activity\IManager;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class BackendTest extends TestCase {

	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $activityManager;

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $userSession;

	protected function setUp() {
		parent::setUp();
		$this->activityManager = $this->createMock(IManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
	}

	/**
	 * @param array $methods
	 * @return Backend|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getBackend(array $methods = []) {
		if (empty($methods)) {
			return new Backend(
				$this->activityManager,
				$this->groupManager,
				$this->userSession
			);
		} else {
			return $this->getMockBuilder(Backend::class)
				->setConstructorArgs([
					$this->activityManager,
					$this->groupManager,
					$this->userSession,
				])
				->setMethods($methods)
				->getMock();
		}
	}

	public function dataCallTriggerCalendarActivity() {
		return [
			['onCalendarAdd', [['data']], Extension::SUBJECT_ADD, [['data'], [], []]],
			['onCalendarUpdate', [['data'], ['shares'], ['changed-properties']], Extension::SUBJECT_UPDATE, [['data'], ['shares'], ['changed-properties']]],
			['onCalendarDelete', [['data'], ['shares']], Extension::SUBJECT_DELETE, [['data'], ['shares'], []]],
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
	public function testCallTriggerCalendarActivity($method, array $payload, $expectedSubject, array $expectedPayload) {
		$backend = $this->getBackend(['triggerCalendarActivity']);
		$backend->expects($this->once())
			->method('triggerCalendarActivity')
			->willReturnCallback(function() use($expectedPayload, $expectedSubject) {
				$arguments = func_get_args();
				$this->assertSame($expectedSubject, array_shift($arguments));
				$this->assertEquals($expectedPayload, $arguments);
			});

		call_user_func_array([$backend, $method], $payload);
	}

	public function dataTriggerCalendarActivity() {
		return [
			// Add calendar
			[Extension::SUBJECT_ADD, [], [], [], '', '', null, []],
			[Extension::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], [], [], '', 'admin', null, ['admin']],
			[Extension::SUBJECT_ADD, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], [], [], 'test2', 'test2', null, ['admin']],

			// Update calendar
			[Extension::SUBJECT_UPDATE, [], [], [], '', '', null, []],
			// No visible change - owner only
			[Extension::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', null, ['admin']],
			// Visible change
			[Extension::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], ['{DAV:}displayname' => 'Name'], '', 'admin', ['user1'], ['user1', 'admin']],
			[Extension::SUBJECT_UPDATE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], ['{DAV:}displayname' => 'Name'], 'test2', 'test2', ['user1'], ['user1', 'admin']],

			// Delete calendar
			[Extension::SUBJECT_DELETE, [], [], [], '', '', null, []],
			[Extension::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', [], ['admin']],
			[Extension::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], '', 'admin', ['user1'], ['user1', 'admin']],
			[Extension::SUBJECT_DELETE, [
				'principaluri' => 'principal/user/admin',
				'id' => 42,
				'{DAV:}displayname' => 'Name of calendar',
			], ['shares'], [], 'test2', 'test2', ['user1'], ['user1', 'admin']],
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
	public function testTriggerCalendarActivity($action, array $data, array $shares, array $changedProperties, $currentUser, $author, $shareUsers, array $users) {
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
				$user = $this->createMock(IUser::class);
				$this->userSession->expects($this->once())
					->method('getUser')
					->willReturn($user);
				$user->expects($this->once())
					->method('getUID')
					->willReturn($currentUser);
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
				->with(Extension::CALENDAR, $data['id'])
				->willReturnSelf();
			$event->expects($this->once())
				->method('setType')
				->with(Extension::CALENDAR)
				->willReturnSelf();
			$event->expects($this->once())
				->method('setAuthor')
				->with($author)
				->willReturnSelf();

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
}
