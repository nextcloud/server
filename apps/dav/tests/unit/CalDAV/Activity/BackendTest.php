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
use OCP\Activity\IManager;
use OCP\IGroupManager;
use OCP\IUserSession;
use Test\TestCase;

class BackendTest extends TestCase {

	/** @var IManager */
	protected $activityManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IUserSession */
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
			['onCalendarUpdate', [['data'], ['shares'], ['properties']], Extension::SUBJECT_UPDATE, [['data'], ['shares'], ['properties']]],
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
}
