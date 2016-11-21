<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\ShareesAPIController;
use OCA\Files_Sharing\Tests\TestCase;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\Http\Client\IClientService;
use OCP\Share;

/**
 * Class ShareesTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests\API
 */
class ShareesAPIControllerTest extends TestCase {
	/** @var ShareesAPIController */
	protected $sharees;

	/** @var \OCP\IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var \OCP\IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $groupManager;

	/** @var \OCP\Contacts\IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $contactsManager;

	/** @var \OCP\IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	protected $session;

	/** @var \OCP\IRequest|\PHPUnit_Framework_MockObject_MockObject */
	protected $request;

	/** @var \OCP\Share\IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $shareManager;

	/** @var IClientService|\PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	protected function setUp() {
		parent::setUp();

		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()
			->getMock();

		$this->groupManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();

		$this->contactsManager = $this->getMockBuilder('OCP\Contacts\IManager')
			->disableOriginalConstructor()
			->getMock();

		$this->session = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();

		$this->request = $this->getMockBuilder('OCP\IRequest')
			->disableOriginalConstructor()
			->getMock();

		$this->shareManager = $this->getMockBuilder('OCP\Share\IManager')
			->disableOriginalConstructor()
			->getMock();

		$this->clientService = $this->createMock(IClientService::class);

		$this->sharees = new ShareesAPIController(
			'files_sharing',
			$this->request,
			$this->groupManager,
			$this->userManager,
			$this->contactsManager,
			$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
			$this->session,
			$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
			$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
			$this->shareManager,
			$this->clientService
		);
	}

	/**
	 * @param string $uid
	 * @param string $displayName
	 * @return \OCP\IUser|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getUserMock($uid, $displayName) {
		$user = $this->getMockBuilder('OCP\IUser')
			->disableOriginalConstructor()
			->getMock();

		$user->expects($this->any())
			->method('getUID')
			->willReturn($uid);

		$user->expects($this->any())
			->method('getDisplayName')
			->willReturn($displayName);

		return $user;
	}

	/**
	 * @param string $gid
	 * @return \OCP\IGroup|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getGroupMock($gid) {
		$group = $this->getMockBuilder('OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();

		$group->expects($this->any())
			->method('getGID')
			->willReturn($gid);

		return $group;
	}

	public function dataGetUsers() {
		return [
			['test', false, true, [], [], [], [], true, false],
			['test', false, false, [], [], [], [], true, false],
			['test', true, true, [], [], [], [], true, false],
			['test', true, false, [], [], [], [], true, false],
			[
				'test', false, true, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', false, false, [], [],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', true, true, [], [],
				[], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', true, false, [], [],
				[], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', true, true, ['test-group'], [['test-group', 'test', 2, 0, []]],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test', true, false, ['test-group'], [['test-group', 'test', 2, 0, []]],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				], [], true, $this->getUserMock('test', 'Test')
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
				true,
				false,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				false,
				true,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test0']],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				false,
				false,
				[],
				[
					$this->getUserMock('test0', 'Test'),
					$this->getUserMock('test1', 'Test One'),
					$this->getUserMock('test2', 'Test Two'),
				],
				[
					['label' => 'Test', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test0']],
				],
				[],
				true,
				false,
			],
			[
				'test',
				true,
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, ['test1' => 'Test One']],
					['xyz', 'test', 2, 0, []],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				],
				true,
				false,
			],
			[
				'test',
				true,
				false,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, ['test1' => 'Test One']],
					['xyz', 'test', 2, 0, []],
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				true,
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
					['xyz', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
				],
				[],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				true,
				false,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
					['xyz', 'test', 2, 0, [
						'test1' => 'Test One',
						'test2' => 'Test Two',
					]],
				],
				[],
				[],
				true,
				false,
			],
			[
				'test',
				true,
				true,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test' => 'Test One',
					]],
					['xyz', 'test', 2, 0, [
						'test2' => 'Test Two',
					]],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				],
				[
					['label' => 'Test Two', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				],
				false,
				false,
			],
			[
				'test',
				true,
				false,
				['abc', 'xyz'],
				[
					['abc', 'test', 2, 0, [
						'test' => 'Test One',
					]],
					['xyz', 'test', 2, 0, [
						'test2' => 'Test Two',
					]],
				],
				[
					['label' => 'Test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test']],
				],
				[],
				true,
				false,
			],
		];
	}

	/**
	 * @dataProvider dataGetUsers
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param array $groupResponse
	 * @param array $userResponse
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param mixed $singleUser
	 */
	public function testGetUsers($searchTerm, $shareWithGroupOnly, $shareeEnumeration, $groupResponse, $userResponse, $exactExpected, $expected, $reachedEnd, $singleUser) {
		$this->invokePrivate($this->sharees, 'limit', [2]);
		$this->invokePrivate($this->sharees, 'offset', [0]);
		$this->invokePrivate($this->sharees, 'shareWithGroupOnly', [$shareWithGroupOnly]);
		$this->invokePrivate($this->sharees, 'shareeEnumeration', [$shareeEnumeration]);

		$user = $this->getUserMock('admin', 'Administrator');
		$this->session->expects($this->any())
			->method('getUser')
			->willReturn($user);

		if (!$shareWithGroupOnly) {
			$this->userManager->expects($this->once())
				->method('searchDisplayName')
				->with($searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
				->willReturn($userResponse);
		} else {
			if ($singleUser !== false) {
				$this->groupManager->expects($this->exactly(2))
					->method('getUserGroupIds')
					->withConsecutive(
						$user,
						$singleUser
					)
					->willReturn($groupResponse);
			} else {
				$this->groupManager->expects($this->once())
					->method('getUserGroupIds')
					->with($user)
					->willReturn($groupResponse);
			}

			$this->groupManager->expects($this->exactly(sizeof($groupResponse)))
				->method('displayNamesInGroup')
				->with($this->anything(), $searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
				->willReturnMap($userResponse);
		}

		if ($singleUser !== false) {
			$this->userManager->expects($this->once())
				->method('get')
				->with($searchTerm)
				->willReturn($singleUser);
		}

		$this->invokePrivate($this->sharees, 'getUsers', [$searchTerm]);
		$result = $this->invokePrivate($this->sharees, 'result');

		$this->assertEquals($exactExpected, $result['exact']['users']);
		$this->assertEquals($expected, $result['users']);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataGetGroups() {
		return [
			['test', false, true, [], [], [], [], true, false],
			['test', false, false, [], [], [], [], true, false],
			[
				'test', false, true,
				[$this->getGroupMock('test1')],
				[],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				true,
				false,
			],
			[
				'test', false, false,
				[$this->getGroupMock('test1')],
				[],
				[],
				[],
				true,
				false,
			],
			[
				'test', false, true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', false, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', false, true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[],
				[],
				true,
				null,
			],
			[
				'test', false, true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
			],
			[
				'test', false, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[],
				true,
				$this->getGroupMock('test'),
			],
			['test', true, true, [], [], [], [], true, false],
			['test', true, false, [], [], [], [], true, false],
			[
				'test', true, true,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test1'),
					$this->getGroupMock('test2'),
				],
				[$this->getGroupMock('test1')],
				[],
				[],
				true,
				false,
			],
			[
				'test', true, true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				false,
				false,
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', true, true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test1')],
				[],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test1')],
				[],
				[],
				true,
				false,
			],
			[
				'test', true, true,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']]],
				false,
				false,
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']]],
				[],
				true,
				false,
			],
			[
				'test', true, true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				null,
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[],
				[],
				true,
				null,
			],
			[
				'test', true, true,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[
					['label' => 'test0', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test0']],
					['label' => 'test1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test1']],
				],
				false,
				$this->getGroupMock('test'),
			],
			[
				'test', true, false,
				[
					$this->getGroupMock('test0'),
					$this->getGroupMock('test1'),
				],
				[$this->getGroupMock('test'), $this->getGroupMock('test0'), $this->getGroupMock('test1')],
				[
					['label' => 'test', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'test']],
				],
				[],
				true,
				$this->getGroupMock('test'),
			],
		];
	}

	/**
	 * @dataProvider dataGetGroups
	 *
	 * @param string $searchTerm
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param array $groupResponse
	 * @param array $userGroupsResponse
	 * @param array $exactExpected
	 * @param array $expected
	 * @param bool $reachedEnd
	 * @param mixed $singleGroup
	 */
	public function testGetGroups($searchTerm, $shareWithGroupOnly, $shareeEnumeration, $groupResponse, $userGroupsResponse, $exactExpected, $expected, $reachedEnd, $singleGroup) {
		$this->invokePrivate($this->sharees, 'limit', [2]);
		$this->invokePrivate($this->sharees, 'offset', [0]);
		$this->invokePrivate($this->sharees, 'shareWithGroupOnly', [$shareWithGroupOnly]);
		$this->invokePrivate($this->sharees, 'shareeEnumeration', [$shareeEnumeration]);

		$this->groupManager->expects($this->once())
			->method('search')
			->with($searchTerm, $this->invokePrivate($this->sharees, 'limit'), $this->invokePrivate($this->sharees, 'offset'))
			->willReturn($groupResponse);

		if ($singleGroup !== false) {
			$this->groupManager->expects($this->once())
				->method('get')
				->with($searchTerm)
				->willReturn($singleGroup);
		}

		if ($shareWithGroupOnly) {
			$user = $this->getUserMock('admin', 'Administrator');
			$this->session->expects($this->any())
				->method('getUser')
				->willReturn($user);

			$numGetUserGroupsCalls = empty($groupResponse) ? 0 : 1;
			$this->groupManager->expects($this->exactly($numGetUserGroupsCalls))
				->method('getUserGroups')
				->with($user)
				->willReturn($userGroupsResponse);
		}

		$this->invokePrivate($this->sharees, 'getGroups', [$searchTerm]);
		$result = $this->invokePrivate($this->sharees, 'result');

		$this->assertEquals($exactExpected, $result['exact']['groups']);
		$this->assertEquals($expected, $result['groups']);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	/**
	 * @dataProvider dataGetRemote
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param bool $shareeEnumeration
	 * @param array $expected
	 * @param bool $reachedEnd
	 */
	public function testGetRemote($searchTerm, $contacts, $shareeEnumeration, $expected, $reachedEnd) {
		$this->invokePrivate($this->sharees, 'shareeEnumeration', [$shareeEnumeration]);
		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['CLOUD', 'FN'])
			->willReturn($contacts);

		$result = $this->invokePrivate($this->sharees, 'getRemote', [$searchTerm]);

		$this->assertEquals($expected, $result);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataGetRemote() {
		return [
			['test', [], true, ['results' => [], 'exact' => [], 'exactIdMatch' => false], true],
			['test', [], false, ['results' => [], 'exact' => [], 'exactIdMatch' => false], true],
			[
				'test@remote',
				[],
				true,
				['results' => [], 'exact' => [['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']]], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote',
				[],
				false,
				['results' => [], 'exact' => [['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']]], 'exactIdMatch' => false],
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost']]], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost']]], 'exact' => [['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']]], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'test@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'test@remote']]], 'exactIdMatch' => false],
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [], 'exact' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost']]], 'exactIdMatch' => true],
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'username@localhost', 'server' => 'localhost']]], 'exactIdMatch' => true],
				true,
			],
			// contact with space
			[
				'user name@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User Name @ Localhost',
						'CLOUD' => [
							'user name@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'User Name @ Localhost (user name@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'user name@localhost', 'server' => 'localhost']]], 'exactIdMatch' => true],
				true,
			],
			// remote with space, no contact
			[
				'user space@remote',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'CLOUD' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'CLOUD' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'user space@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'user space@remote']]], 'exactIdMatch' => false],
				true,
			],
		];
	}

	/**
	 * @dataProvider dataGetEmail
	 *
	 * @param string $searchTerm
	 * @param array $contacts
	 * @param bool $shareeEnumeration
	 * @param array $expected
	 * @param bool $reachedEnd
	 */
	public function testGetEmail($searchTerm, $contacts, $shareeEnumeration, $expected, $reachedEnd) {
		$this->invokePrivate($this->sharees, 'shareeEnumeration', [$shareeEnumeration]);
		$this->contactsManager->expects($this->any())
			->method('search')
			->with($searchTerm, ['EMAIL', 'FN'])
			->willReturn($contacts);

		$result = $this->invokePrivate($this->sharees, 'getEmail', [$searchTerm]);

		$this->assertEquals($expected, $result);
		$this->assertCount((int) $reachedEnd, $this->invokePrivate($this->sharees, 'reachedEndFor'));
	}

	public function dataGetEmail() {
		return [
			['test', [], true, ['results' => [], 'exact' => [], 'exactIdMatch' => false], true],
			['test', [], false, ['results' => [], 'exact' => [], 'exactIdMatch' => false], true],
			[
				'test@remote.com',
				[],
				true,
				['results' => [], 'exact' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]], 'exactIdMatch' => false],
				true,
			],
			[ // no valid email address
				'test@remote',
				[],
				true,
				['results' => [], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote.com',
				[],
				false,
				['results' => [], 'exact' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]], 'exactIdMatch' => false],
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
			[
				'test',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exact' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]], 'exactIdMatch' => false],
				true,
			],
			[
				'test@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'test@remote.com', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'test@remote.com']]], 'exactIdMatch' => false],
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				true,
				['results' => [], 'exact' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exactIdMatch' => true],
				true,
			],
			[
				'username@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'User @ Localhost (username@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'username@localhost']]], 'exactIdMatch' => true],
				true,
			],
			// contact with space
			[
				'user name@localhost',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User Name @ Localhost',
						'EMAIL' => [
							'user name@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [['label' => 'User Name @ Localhost (user name@localhost)', 'value' => ['shareType' => Share::SHARE_TYPE_EMAIL, 'shareWith' => 'user name@localhost']]], 'exactIdMatch' => true],
				true,
			],
			// remote with space, no contact
			[
				'user space@remote.com',
				[
					[
						'FN' => 'User3 @ Localhost',
					],
					[
						'FN' => 'User2 @ Localhost',
						'EMAIL' => [
						],
					],
					[
						'FN' => 'User @ Localhost',
						'EMAIL' => [
							'username@localhost',
						],
					],
				],
				false,
				['results' => [], 'exact' => [], 'exactIdMatch' => false],
				true,
			],
		];
	}

	public function dataSearch() {
		$noRemote = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_EMAIL];
		$allTypes = [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE, Share::SHARE_TYPE_EMAIL];

		return [
			[[], '', 'yes', true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'search' => '',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'search' => 'foobar',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'search' => 0,
			], '', 'yes', true, true, $noRemote, false, true, true],

			// Test itemType
			[[
				'itemType' => '',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 0,
			], '', 'yes', true, true, $noRemote, false, true, true],

			// Test shareType
			[[
				'itemType' => 'call',
			], '', 'yes', true, true, $noRemote, false, true, true],
			[[
				'itemType' => 'folder',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 0,
			], '', 'yes', true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => '0',
			], '', 'yes', true, false, [0], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 1,
			], '', 'yes', true, false, [1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 12,
			], '', 'yes', true, false, [], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => 'foobar',
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => [0, 1, 2],
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => [0, 1],
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, false, [0, 1], false, true, true],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', true, false, [0, 6], false, true, false],
			[[
				'itemType' => 'folder',
				'shareType' => $allTypes,
			], '', 'yes', false, true, [0, 4], false, true, false],

			// Test pagination
			[[
				'itemType' => 'folder',
				'page' => 1,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'page' => 10,
			], '', 'yes', true, true, $allTypes, false, true, true],

			// Test perPage
			[[
				'itemType' => 'folder',
				'perPage' => 1,
			], '', 'yes', true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
				'perPage' => 10,
			], '', 'yes', true, true, $allTypes, false, true, true],

			// Test $shareWithGroupOnly setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes',  true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'yes', 'yes', true, true, $allTypes, true, true, true],

			// Test $shareeEnumeration setting
			[[
				'itemType' => 'folder',
			], 'no', 'yes',  true, true, $allTypes, false, true, true],
			[[
				'itemType' => 'folder',
			], 'no', 'no', true, true, $allTypes, false, false, true],
		];
	}

	/**
	 * @dataProvider dataSearch
	 *
	 * @param array $getData
	 * @param string $apiSetting
	 * @param string $enumSetting
	 * @param bool $remoteSharingEnabled
	 * @param bool $emailSharingEnabled
	 * @param array $shareTypes
	 * @param bool $shareWithGroupOnly
	 * @param bool $shareeEnumeration
	 * @param bool $allowGroupSharing
	 */
	public function testSearch($getData, $apiSetting, $enumSetting, $remoteSharingEnabled, $emailSharingEnabled, $shareTypes, $shareWithGroupOnly, $shareeEnumeration, $allowGroupSharing) {
		$search = isset($getData['search']) ? $getData['search'] : '';
		$itemType = isset($getData['itemType']) ? $getData['itemType'] : null;
		$page = isset($getData['page']) ? $getData['page'] : 1;
		$perPage = isset($getData['perPage']) ? $getData['perPage'] : 200;
		$shareType = isset($getData['shareType']) ? $getData['shareType'] : null;

		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->exactly(2))
			->method('getAppValue')
			->with('core', $this->anything(), $this->anything())
			->willReturnMap([
				['core', 'shareapi_only_share_with_group_members', 'no', $apiSetting],
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'yes', $enumSetting],
			]);

		$this->shareManager->expects($itemType === 'file' || $itemType === 'folder' ? $this->once() : $this->never())
			->method('allowGroupSharing')
			->willReturn($allowGroupSharing);

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\Controller\ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock(),
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$config,
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
				$this->shareManager,
				$this->clientService
			])
			->setMethods(array('searchSharees', 'isRemoteSharingAllowed', 'shareProviderExists'))
			->getMock();
		$sharees->expects($this->once())
			->method('searchSharees')
			->willReturnCallback(function
					($isearch, $iitemType, $ishareTypes, $ipage, $iperPage)
				use ($search, $itemType, $shareTypes, $page, $perPage) {

				// We are doing strict comparisons here, so we can differ 0/'' and null on shareType/itemType
				$this->assertSame($search, $isearch);
				$this->assertSame($itemType, $iitemType);
				$this->assertSame(count($shareTypes), count($ishareTypes));
				foreach($shareTypes as $expected) {
					$this->assertTrue(in_array($expected, $ishareTypes));
				}
				$this->assertSame($page, $ipage);
				$this->assertSame($perPage, $iperPage);
				return new Http\DataResponse();
			});
		$sharees->expects($this->any())
			->method('isRemoteSharingAllowed')
			->with($itemType)
			->willReturn($remoteSharingEnabled);

		$this->shareManager->expects($this->any())
			->method('shareProviderExists')
			->with(\OCP\Share::SHARE_TYPE_EMAIL)
			->willReturn($emailSharingEnabled);

		$this->assertInstanceOf(Http\DataResponse::class, $sharees->search($search, $itemType, $page, $perPage, $shareType));

		$this->assertSame($shareWithGroupOnly, $this->invokePrivate($sharees, 'shareWithGroupOnly'));
		$this->assertSame($shareeEnumeration, $this->invokePrivate($sharees, 'shareeEnumeration'));
	}

	public function dataSearchInvalid() {
		return [
			// Test invalid pagination
			[[
				'page' => 0,
			], 'Invalid page'],
			[[
				'page' => '0',
			], 'Invalid page'],
			[[
				'page' => -1,
			], 'Invalid page'],

			// Test invalid perPage
			[[
				'perPage' => 0,
			], 'Invalid perPage argument'],
			[[
				'perPage' => '0',
			], 'Invalid perPage argument'],
			[[
				'perPage' => -1,
			], 'Invalid perPage argument'],
		];
	}

	/**
	 * @dataProvider dataSearchInvalid
	 *
	 * @param array $getData
	 * @param string $message
	 */
	public function testSearchInvalid($getData, $message) {
		$page = isset($getData['page']) ? $getData['page'] : 1;
		$perPage = isset($getData['perPage']) ? $getData['perPage'] : 200;

		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config->expects($this->never())
			->method('getAppValue');

		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\Controller\ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock(),
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$config,
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
				$this->shareManager,
				$this->clientService
			])
			->setMethods(array('searchSharees', 'isRemoteSharingAllowed'))
			->getMock();
		$sharees->expects($this->never())
			->method('searchSharees');
		$sharees->expects($this->never())
			->method('isRemoteSharingAllowed');

		try {
			$sharees->search('', null, $page, $perPage, null);
			$this->fail();
		} catch (OCSBadRequestException $e) {
			$this->assertEquals($message, $e->getMessage());
		}
	}

	public function dataIsRemoteSharingAllowed() {
		return [
			['file', true],
			['folder', true],
			['', false],
			['contacts', false],
		];
	}

	/**
	 * @dataProvider dataIsRemoteSharingAllowed
	 *
	 * @param string $itemType
	 * @param bool $expected
	 */
	public function testIsRemoteSharingAllowed($itemType, $expected) {
		$this->assertSame($expected, $this->invokePrivate($this->sharees, 'isRemoteSharingAllowed', [$itemType]));
	}

	public function dataSearchSharees() {
		return [
			['test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], ['results' => [], 'exact' => [], 'exactIdMatch' => false],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
					'emails' => [],
					'lookup' => [],
				], false],
			['test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [], [], ['results' => [], 'exact' => [], 'exactIdMatch' => false],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [],
					'groups' => [],
					'remotes' => [],
					'emails' => [],
					'lookup' => [],
				], false],
			[
				'test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_GROUP, Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], [
					['label' => 'testgroup1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
				], [
					'results' => [['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']]], 'exact' => [], 'exactIdMatch' => false,
				],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [
						['label' => 'testgroup1', 'value' => ['shareType' => Share::SHARE_TYPE_GROUP, 'shareWith' => 'testgroup1']],
					],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
					'emails' => [],
					'lookup' => [],
				], true,
			],
			// No groups requested
			[
				'test', 'folder', [Share::SHARE_TYPE_USER, Share::SHARE_TYPE_REMOTE], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, [
					'results' => [['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']]], 'exact' => [], 'exactIdMatch' => false
				],
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [],
					'remotes' => [
						['label' => 'testz@remote', 'value' => ['shareType' => Share::SHARE_TYPE_REMOTE, 'shareWith' => 'testz@remote']],
					],
					'emails' => [],
					'lookup' => [],
				], false,
			],
			// Share type restricted to user - Only one user
			[
				'test', 'folder', [Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
				], null, null,
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [
						['label' => 'test One', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					],
					'groups' => [],
					'remotes' => [],
					'emails' => [],
					'lookup' => [],
				], false,
			],
			// Share type restricted to user - Multipage result
			[
				'test', 'folder', [Share::SHARE_TYPE_USER], 1, 2, false, [
					['label' => 'test 1', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
					['label' => 'test 2', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
				], null, null,
				[
					'exact' => ['users' => [], 'groups' => [], 'remotes' => [], 'emails' => []],
					'users' => [
						['label' => 'test 1', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test1']],
						['label' => 'test 2', 'value' => ['shareType' => Share::SHARE_TYPE_USER, 'shareWith' => 'test2']],
					],
					'groups' => [],
					'remotes' => [],
					'emails' => [],
					'lookup' => [],
				], true,
			],
		];
	}

	/**
	 * @dataProvider dataSearchSharees
	 *
	 * @param string $searchTerm
	 * @param string $itemType
	 * @param array $shareTypes
	 * @param int $page
	 * @param int $perPage
	 * @param bool $shareWithGroupOnly
	 * @param array $mockedUserResult
	 * @param array $mockedGroupsResult
	 * @param array $mockedRemotesResult
	 * @param array $expected
	 * @param bool $nextLink
	 */
	public function testSearchSharees($searchTerm, $itemType, array $shareTypes, $page, $perPage, $shareWithGroupOnly,
									  $mockedUserResult, $mockedGroupsResult, $mockedRemotesResult, $expected, $nextLink) {
		/** @var \PHPUnit_Framework_MockObject_MockObject|\OCA\Files_Sharing\Controller\ShareesAPIController $sharees */
		$sharees = $this->getMockBuilder('\OCA\Files_Sharing\Controller\ShareesAPIController')
			->setConstructorArgs([
				'files_sharing',
				$this->getMockBuilder('OCP\IRequest')->disableOriginalConstructor()->getMock(),
				$this->groupManager,
				$this->userManager,
				$this->contactsManager,
				$this->getMockBuilder('OCP\IConfig')->disableOriginalConstructor()->getMock(),
				$this->session,
				$this->getMockBuilder('OCP\IURLGenerator')->disableOriginalConstructor()->getMock(),
				$this->getMockBuilder('OCP\ILogger')->disableOriginalConstructor()->getMock(),
				$this->shareManager,
				$this->clientService
			])
			->setMethods(array('getShareesForShareIds', 'getUsers', 'getGroups', 'getRemote'))
			->getMock();
		$sharees->expects(($mockedUserResult === null) ? $this->never() : $this->once())
			->method('getUsers')
			->with($searchTerm)
			->willReturnCallback(function() use ($sharees, $mockedUserResult) {
				$result = $this->invokePrivate($sharees, 'result');
				$result['users'] = $mockedUserResult;
				$this->invokePrivate($sharees, 'result', [$result]);
			});
		$sharees->expects(($mockedGroupsResult === null) ? $this->never() : $this->once())
			->method('getGroups')
			->with($searchTerm)
			->willReturnCallback(function() use ($sharees, $mockedGroupsResult) {
				$result = $this->invokePrivate($sharees, 'result');
				$result['groups'] = $mockedGroupsResult;
				$this->invokePrivate($sharees, 'result', [$result]);
			});

		$sharees->expects(($mockedRemotesResult === null) ? $this->never() : $this->once())
			->method('getRemote')
			->with($searchTerm)
			->willReturn($mockedRemotesResult);

		$ocs = $this->invokePrivate($sharees, 'searchSharees', [$searchTerm, $itemType, $shareTypes, $page, $perPage, $shareWithGroupOnly]);
		$this->assertInstanceOf('\OCP\AppFramework\Http\DataResponse', $ocs);
		$this->assertEquals($expected, $ocs->getData());

		// Check if next link is set
		if ($nextLink) {
			$headers = $ocs->getHeaders();
			$this->assertArrayHasKey('Link', $headers);
			$this->assertStringStartsWith('<', $headers['Link']);
			$this->assertStringEndsWith('>; rel="next"', $headers['Link']);
		}
	}

	/**
	 * @expectedException \OCP\AppFramework\OCS\OCSBadRequestException
	 * @expectedExceptionMessage Missing itemType
	 */
	public function testSearchShareesNoItemType() {
		$this->invokePrivate($this->sharees, 'searchSharees', ['', null, [], [], 0, 0, false]);
	}

	public function dataGetPaginationLink() {
		return [
			[1, '/ocs/v1.php', ['perPage' => 2], '<?perPage=2&page=2>; rel="next"'],
			[10, '/ocs/v2.php', ['perPage' => 2], '<?perPage=2&page=11>; rel="next"'],
		];
	}

	/**
	 * @dataProvider dataGetPaginationLink
	 *
	 * @param int $page
	 * @param string $scriptName
	 * @param array $params
	 * @param array $expected
	 */
	public function testGetPaginationLink($page, $scriptName, $params, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'getPaginationLink', [$page, $params]));
	}

	public function dataIsV2() {
		return [
			['/ocs/v1.php', false],
			['/ocs/v2.php', true],
		];
	}

	/**
	 * @dataProvider dataIsV2
	 *
	 * @param string $scriptName
	 * @param bool $expected
	 */
	public function testIsV2($scriptName, $expected) {
		$this->request->expects($this->once())
			->method('getScriptName')
			->willReturn($scriptName);

		$this->assertEquals($expected, $this->invokePrivate($this->sharees, 'isV2'));
	}

	/**
	 * @dataProvider dataTestSplitUserRemote
	 *
	 * @param string $remote
	 * @param string $expectedUser
	 * @param string $expectedUrl
	 */
	public function testSplitUserRemote($remote, $expectedUser, $expectedUrl) {
		list($remoteUser, $remoteUrl) = $this->sharees->splitUserRemote($remote);
		$this->assertSame($expectedUser, $remoteUser);
		$this->assertSame($expectedUrl, $remoteUrl);
	}

	public function dataTestSplitUserRemote() {
		$userPrefix = ['user@name', 'username'];
		$protocols = ['', 'http://', 'https://'];
		$remotes = [
			'localhost',
			'local.host',
			'dev.local.host',
			'dev.local.host/path',
			'dev.local.host/at@inpath',
			'127.0.0.1',
			'::1',
			'::192.0.2.128',
			'::192.0.2.128/at@inpath',
		];

		$testCases = [];
		foreach ($userPrefix as $user) {
			foreach ($remotes as $remote) {
				foreach ($protocols as $protocol) {
					$baseUrl = $user . '@' . $protocol . $remote;

					$testCases[] = [$baseUrl, $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php', $user, $protocol . $remote];
					$testCases[] = [$baseUrl . '/index.php/s/token', $user, $protocol . $remote];
				}
			}
		}
		return $testCases;
	}

	public function dataTestSplitUserRemoteError() {
		return array(
			// Invalid path
			array('user@'),

			// Invalid user
			array('@server'),
			array('us/er@server'),
			array('us:er@server'),

			// Invalid splitting
			array('user'),
			array(''),
			array('us/erserver'),
			array('us:erserver'),
		);
	}

	/**
	 * @dataProvider dataTestSplitUserRemoteError
	 *
	 * @param string $id
	 * @expectedException \Exception
	 */
	public function testSplitUserRemoteError($id) {
		$this->sharees->splitUserRemote($id);
	}

	/**
	 * @dataProvider dataTestFixRemoteUrl
	 *
	 * @param string $url
	 * @param string $expected
	 */
	public function testFixRemoteUrl($url, $expected) {
		$this->assertSame($expected,
			$this->invokePrivate($this->sharees, 'fixRemoteURL', [$url])
		);
	}

	public function dataTestFixRemoteUrl() {
		return [
			['http://localhost', 'http://localhost'],
			['http://localhost/', 'http://localhost'],
			['http://localhost/index.php', 'http://localhost'],
			['http://localhost/index.php/s/AShareToken', 'http://localhost'],
		];
	}
}
