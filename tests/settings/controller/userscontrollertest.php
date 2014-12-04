<?php
/**
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Settings\Controller;

use \OC\Settings\Application;
use OCP\AppFramework\Http\DataResponse;

/**
 * @package OC\Settings\Controller
 */
class UsersControllerTest extends \Test\TestCase {

	/** @var \OCP\AppFramework\IAppContainer */
	private $container;

	/** @var UsersController */
	private $usersController;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['AppName'] = 'settings';
		$this->container['GroupManager'] = $this->getMockBuilder('\OCP\IGroupManager')
			->disableOriginalConstructor()->getMock();
		$this->container['UserManager'] = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession'] = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N'] = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['IsAdmin'] = true;
		$this->container['L10N']
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->usersController = $this->container['UsersController'];

	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndex() {
		$admin = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$admin
			->method('getLastLogin')
			->will($this->returnValue(12));
		$admin
			->method('getHome')
			->will($this->returnValue('/home/admin'));
		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->method('getLastLogin')
			->will($this->returnValue(500));
		$foo
			->method('getHome')
			->will($this->returnValue('/home/foo'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));

		$this->container['GroupManager']
			->expects($this->once())
			->method('displayNamesInGroup')
			->will($this->returnValue(array('foo' => 'M. Foo', 'admin' => 'S. Admin', 'bar' => 'B. Ar')));
		$this->container['GroupManager']
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));
		$this->container['UserManager']
			->expects($this->exactly(3))
			->method('get')
			->will($this->onConsecutiveCalls($foo, $admin, $bar));
		$this->container['Config']
			->expects($this->exactly(3))
			->method('getUserValue')
			->will($this->onConsecutiveCalls(1024, 404, 2323));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'success',
				'data' => array(
					0 => array(
						'name' => 'foo',
						'displayname' => 'M. Foo',
						'groups' => array('Users', 'Support'),
						'subadmin' => array(),
						'quota' => 1024,
						'storageLocation' => '/home/foo',
						'lastLogin' => 500
					),
					1 => array(
						'name' => 'admin',
						'displayname' => 'S. Admin',
						'groups' => array('admins', 'Support'),
						'subadmin' => array(),
						'quota' => 404,
						'storageLocation' => '/home/admin',
						'lastLogin' => 12
					),
					2 => array(
						'name' => 'bar',
						'displayname' => 'B. Ar',
						'groups' => array('External Users'),
						'subadmin' => array(),
						'quota' => 2323,
						'storageLocation' => '/home/bar',
						'lastLogin' => 3999
					),
				)
			)
		);
		$response = $this->usersController->index(0, 10, 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testCreateSuccessfulWithoutGroup() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->method('getHome')
			->will($this->returnValue('/home/user'));

		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->onConsecutiveCalls($user));


		$expectedResponse = new DataResponse(
			array(
				'status' => 'success',
				'data' => array(
					'username' => 'foo',
					'groups' => null,
					'storageLocation' => '/home/user'
				)
			)
		);
		$response = $this->usersController->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testCreateSuccessfulWithGroup() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$user
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$existingGroup = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$existingGroup
			->expects($this->once())
			->method('addUser')
			->with($user);
		$newGroup = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$newGroup
			->expects($this->once())
			->method('addUser')
			->with($user);

		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->onConsecutiveCalls($user));
		$this->container['GroupManager']
			->expects($this->exactly(2))
			->method('get')
			->will($this->onConsecutiveCalls(null, $existingGroup));
		$this->container['GroupManager']
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup')
			->will($this->onConsecutiveCalls($newGroup));
		$this->container['GroupManager']
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->onConsecutiveCalls(array('NewGroup', 'ExistingGroup')));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'success',
				'data' => array(
					'username' => 'foo',
					'groups' => array('NewGroup', 'ExistingGroup'),
					'storageLocation' => '/home/user'
				)
			)
		);
		$response = $this->usersController->create('foo', 'password', array('NewGroup', 'ExistingGroup'));
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testCreateUnsuccessful() {
		$this->container['UserManager']
			->method('createUser')
			->will($this->throwException(new \Exception()));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => 'Unable to create user.'
				)
			)
		);
		$response = $this->usersController->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testDestroySelf() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => 'Unable to delete user.'
				)
			)
		);
		$response = $this->usersController->destroy('myself');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testDestroy() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('Admin'));
		$toDeleteUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$toDeleteUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(true));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));
		$this->container['UserManager']
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($toDeleteUser));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'success',
				'data' => array(
					'username' => 'UserToDelete'
				)
			)
		);
		$response = $this->usersController->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}
}
