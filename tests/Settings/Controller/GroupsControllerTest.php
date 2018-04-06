<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Tests\Settings\Controller;

use OC\Group\Group;
use OC\Group\MetaData;
use OC\Settings\Controller\GroupsController;
use OC\User\User;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

/**
 * @package Tests\Settings\Controller
 */
class GroupsControllerTest extends \Test\TestCase {

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;

	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;

	/** @var GroupsController */
	private $groupsController;

	protected function setUp() {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$l = $this->createMock(IL10N::class);
		$l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));
		$this->groupsController = new GroupsController(
			'settings',
			$this->createMock(IRequest::class),
			$this->groupManager,
			$this->userSession,
			true,
			$l
		);

	}

	/**
	 * TODO: Since GroupManager uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndexSortByName() {
		$firstGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$firstGroup
			->method('getGID')
			->will($this->returnValue('firstGroup'));
		$firstGroup
			->method('getDisplayName')
			->will($this->returnValue('1st group'));
		$firstGroup
			->method('count')
			->will($this->returnValue(12));
		$secondGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$secondGroup
			->method('getGID')
			->will($this->returnValue('secondGroup'));
		$secondGroup
			->method('getDisplayName')
			->will($this->returnValue('2nd group'));
		$secondGroup
			->method('count')
			->will($this->returnValue(25));
		$thirdGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$thirdGroup
			->method('getGID')
			->will($this->returnValue('thirdGroup'));
		$thirdGroup
			->method('getDisplayName')
			->will($this->returnValue('3rd group'));
		$thirdGroup
			->method('count')
			->will($this->returnValue(14));
		$fourthGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$fourthGroup
			->method('getGID')
			->will($this->returnValue('admin'));
		$fourthGroup
			->method('getDisplayName')
			->will($this->returnValue('Administrators'));
		$fourthGroup
			->method('count')
			->will($this->returnValue(18));
		/** @var \OC\Group\Group[] $groups */
		$groups = array();
		$groups[] = $firstGroup;
		$groups[] = $secondGroup;
		$groups[] = $thirdGroup;
		$groups[] = $fourthGroup;

		$user = $this->getMockBuilder(User::class)
			->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyAdminUser'));
		$this->groupManager->method('search')
			->will($this->returnValue($groups));

		$expectedResponse = new DataResponse(
			array(
				'data' => array(
				'adminGroups' => array(
					0 => array(
						'id' => 'admin',
						'name' => 'Administrators',
						'usercount' => 0,//User count disabled 18,
					)
				),
				'groups' =>
					array(
						0 => array(
							'id' => 'firstGroup',
							'name' => '1st group',
							'usercount' => 0,//User count disabled 12,
						),
						1 => array(
							'id' => 'secondGroup',
							'name' => '2nd group',
							'usercount' => 0,//User count disabled 25,
						),
						2 => array(
							'id' => 'thirdGroup',
							'name' => '3rd group',
							'usercount' => 0,//User count disabled 14,
						),
					)
				)
			)
		);
		$response = $this->groupsController->index('', false, MetaData::SORT_GROUPNAME);
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since GroupManager uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndexSortbyCount() {
		$firstGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$firstGroup
			->method('getGID')
			->will($this->returnValue('firstGroup'));
		$firstGroup
			->method('getDisplayName')
			->will($this->returnValue('1st group'));
		$firstGroup
			->method('count')
			->will($this->returnValue(12));
		$secondGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$secondGroup
			->method('getGID')
			->will($this->returnValue('secondGroup'));
		$secondGroup
			->method('getDisplayName')
			->will($this->returnValue('2nd group'));
		$secondGroup
			->method('count')
			->will($this->returnValue(25));
		$thirdGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$thirdGroup
			->method('getGID')
			->will($this->returnValue('thirdGroup'));
		$thirdGroup
			->method('getDisplayName')
			->will($this->returnValue('3rd group'));
		$thirdGroup
			->method('count')
			->will($this->returnValue(14));
		$fourthGroup = $this->getMockBuilder(Group::class)
			->disableOriginalConstructor()->getMock();
		$fourthGroup
			->method('getGID')
			->will($this->returnValue('admin'));
		$fourthGroup
			->method('getDisplayName')
			->will($this->returnValue('Administrators'));
		$fourthGroup
			->method('count')
			->will($this->returnValue(18));
		/** @var \OC\Group\Group[] $groups */
		$groups = array();
		$groups[] = $firstGroup;
		$groups[] = $secondGroup;
		$groups[] = $thirdGroup;
		$groups[] = $fourthGroup;

		$user = $this->getMockBuilder(User::class)
			->disableOriginalConstructor()->getMock();
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('MyAdminUser'));
		$this->groupManager
			->method('search')
			->will($this->returnValue($groups));

		$expectedResponse = new DataResponse(
			array(
				'data' => array(
				'adminGroups' => array(
					0 => array(
						'id' => 'admin',
						'name' => 'Administrators',
						'usercount' => 18,
					)
				),
				'groups' =>
					array(
						0 => array(
							'id' => 'secondGroup',
							'name' => '2nd group',
							'usercount' => 25,
						),
						1 => array(
							'id' => 'thirdGroup',
							'name' => '3rd group',
							'usercount' => 14,
						),
						2 => array(
							'id' => 'firstGroup',
							'name' => '1st group',
							'usercount' => 12,
						),
					)
				)
			)
		);
		$response = $this->groupsController->index();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateWithExistingGroup() {
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('ExistingGroup')
			->will($this->returnValue(true));

		$expectedResponse = new DataResponse(
			array(
				'message' => 'Group already exists.'
			),
			Http::STATUS_CONFLICT
		);
		$response = $this->groupsController->create('ExistingGroup');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessful() {
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('NewGroup')
			->will($this->returnValue(false));

		$group = $this->createMock(IGroup::class);
		$group->method('getDisplayName')
			->willReturn('New group');
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup')
			->willReturn($group);

		$expectedResponse = new DataResponse(
			array(
				'groupname' => 'New group'
			),
			Http::STATUS_CREATED
		);
		$response = $this->groupsController->create('NewGroup');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateUnsuccessful() {
		$this->groupManager
			->expects($this->once())
			->method('groupExists')
			->with('NewGroup')
			->will($this->returnValue(false));
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup')
			->will($this->returnValue(false));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'error',
				'data' => array('message' => 'Unable to add group.')
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $this->groupsController->create('NewGroup');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroySuccessful() {
		$group = $this->createMock(IGroup::class);
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ExistingGroup')
			->will($this->returnValue($group));
		$group->method('getDisplayName')
			->willReturn('Existing group');
		$group
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(true));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'success',
				'data' => array('groupname' => 'Existing group')
			),
			Http::STATUS_NO_CONTENT
		);
		$response = $this->groupsController->destroy('ExistingGroup');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyUnsuccessful() {
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with('ExistingGroup')
			->will($this->returnValue(null));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'error',
				'data' => array('message' => 'Unable to delete group.')
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $this->groupsController->destroy('ExistingGroup');
		$this->assertEquals($expectedResponse, $response);
	}

}
