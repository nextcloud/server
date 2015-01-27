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
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * @package OC\Settings\Controller
 */
class UsersControllerTest extends \Test\TestCase {

	/** @var \OCP\AppFramework\IAppContainer */
	private $container;

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
		$this->container['Defaults'] = $this->getMockBuilder('\OC_Defaults')
			->disableOriginalConstructor()->getMock();
		$this->container['Mail'] = $this->getMockBuilder('\OC_Mail')
			->disableOriginalConstructor()->getMock();
		$this->container['DefaultMailAddress'] = 'no-reply@owncloud.com';
		$this->container['Logger'] = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()->getMock();
		$this->container['URLGenerator'] = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()->getMock();
		$this->container['OCP\\App\\IAppManager'] = $this->getMockBuilder('OCP\\App\\IAppManager')
			->disableOriginalConstructor()->getMock();
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndex() {
		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('foo'));
		$foo
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$foo
			->method('getLastLogin')
			->will($this->returnValue(500));
		$foo
			->method('getHome')
			->will($this->returnValue('/home/foo'));
		$foo
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Database'));
		$admin = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$admin
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('admin'));
		$admin
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('S. Admin'));
		$admin
			->expects($this->once())
			->method('getLastLogin')
			->will($this->returnValue(12));
		$admin
			->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/home/admin'));
		$admin
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Dummy'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('bar'));
		$bar
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('B. Ar'));
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));
		$bar
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Dummy'));

		$this->container['GroupManager']
			->expects($this->once())
			->method('displayNamesInGroup')
			->with('gid', 'pattern')
			->will($this->returnValue(array('foo' => 'M. Foo', 'admin' => 'S. Admin', 'bar' => 'B. Ar')));
		$this->container['GroupManager']
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));
		$this->container['UserManager']
			->expects($this->at(0))
			->method('get')
			->with('foo')
			->will($this->returnValue($foo));
		$this->container['UserManager']
			->expects($this->at(1))
			->method('get')
			->with('admin')
			->will($this->returnValue($admin));
		$this->container['UserManager']
			->expects($this->at(2))
			->method('get')
			->with('bar')
			->will($this->returnValue($bar));
		$this->container['Config']
			->expects($this->exactly(6))
			->method('getUserValue')
			->will($this->onConsecutiveCalls(1024, 'foo@bar.com',
											404, 'admin@bar.com',
											2323, 'bar@dummy.com'));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => array('Users', 'Support'),
					'subadmin' => array(),
					'quota' => 1024,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500,
					'backend' => 'OC_User_Database',
					'email' => 'foo@bar.com',
					'isRestoreDisabled' => false,
				),
				1 => array(
					'name' => 'admin',
					'displayname' => 'S. Admin',
					'groups' => array('admins', 'Support'),
					'subadmin' => array(),
					'quota' => 404,
					'storageLocation' => '/home/admin',
					'lastLogin' => 12,
					'backend' => 'OC_User_Dummy',
					'email' => 'admin@bar.com',
					'isRestoreDisabled' => false,
				),
				2 => array(
					'name' => 'bar',
					'displayname' => 'B. Ar',
					'groups' => array('External Users'),
					'subadmin' => array(),
					'quota' => 2323,
					'storageLocation' => '/home/bar',
					'lastLogin' => 3999,
					'backend' => 'OC_User_Dummy',
					'email' => 'bar@dummy.com',
					'isRestoreDisabled' => false,
				),
			)
		);
		$response = $this->container['UsersController']->index(0, 10, 'gid', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndexWithSearch() {
		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('foo'));
		$foo
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$foo
			->method('getLastLogin')
			->will($this->returnValue(500));
		$foo
			->method('getHome')
			->will($this->returnValue('/home/foo'));
		$foo
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Database'));
		$admin = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$admin
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('admin'));
		$admin
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('S. Admin'));
		$admin
			->expects($this->once())
			->method('getLastLogin')
			->will($this->returnValue(12));
		$admin
			->expects($this->once())
			->method('getHome')
			->will($this->returnValue('/home/admin'));
		$admin
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Dummy'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('bar'));
		$bar
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('B. Ar'));
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));
		$bar
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Dummy'));

		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('pattern', 10, 0)
			->will($this->returnValue([$foo, $admin, $bar]));
		$this->container['GroupManager']
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));
		$this->container['Config']
			->expects($this->exactly(6))
			->method('getUserValue')
			->will($this->onConsecutiveCalls(1024, 'foo@bar.com',
				404, 'admin@bar.com',
				2323, 'bar@dummy.com'));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => array('Users', 'Support'),
					'subadmin' => array(),
					'quota' => 1024,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500,
					'backend' => 'OC_User_Database',
					'email' => 'foo@bar.com',
					'isRestoreDisabled' => false,
				),
				1 => array(
					'name' => 'admin',
					'displayname' => 'S. Admin',
					'groups' => array('admins', 'Support'),
					'subadmin' => array(),
					'quota' => 404,
					'storageLocation' => '/home/admin',
					'lastLogin' => 12,
					'backend' => 'OC_User_Dummy',
					'email' => 'admin@bar.com',
					'isRestoreDisabled' => false,
				),
				2 => array(
					'name' => 'bar',
					'displayname' => 'B. Ar',
					'groups' => array('External Users'),
					'subadmin' => array(),
					'quota' => 2323,
					'storageLocation' => '/home/bar',
					'lastLogin' => 3999,
					'backend' => 'OC_User_Dummy',
					'email' => 'bar@dummy.com',
					'isRestoreDisabled' => false,
				),
			)
		);
		$response = $this->container['UsersController']->index(0, 10, '', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}


	public function testIndexWithBackend() {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->exactly(4))
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$user
			->method('getLastLogin')
			->will($this->returnValue(500));
		$user
			->method('getHome')
			->will($this->returnValue('/home/foo'));
		$user
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('OC_User_Database'));
		$this->container['UserManager']
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([new \OC_User_Dummy(), new \OC_User_Database()]));
		$this->container['UserManager']
			->expects($this->once())
			->method('clearBackends');
		$this->container['UserManager']
			->expects($this->once())
			->method('registerBackend')
			->with(new \OC_User_Dummy());
		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$user]));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => null,
					'subadmin' => array(),
					'quota' => null,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500,
					'backend' => 'OC_User_Database',
					'email' => null,
					'isRestoreDisabled' => false,
				)
			)
		);
		$response = $this->container['UsersController']->index(0, 10, '','', 'OC_User_Dummy');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexWithBackendNoUser() {
		$this->container['UserManager']
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([new \OC_User_Dummy(), new \OC_User_Database()]));
		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$expectedResponse = new DataResponse([]);
		$response = $this->container['UsersController']->index(0, 10, '','', 'OC_User_Dummy');
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
		$user
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('bar'));

		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->onConsecutiveCalls($user));


		$expectedResponse = new DataResponse(
			array(
				'name' => 'foo',
				'groups' => null,
				'storageLocation' => '/home/user',
				'backend' => 'bar',
				'lastLogin' => null,
				'displayname' => null,
				'quota' => null,
				'subadmin' => array(),
				'email' => null,
				'isRestoreDisabled' => false,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password', array());
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
		$user
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('bar'));
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
				'name' => 'foo',
				'groups' => array('NewGroup', 'ExistingGroup'),
				'storageLocation' => '/home/user',
				'backend' => 'bar',
				'lastLogin' => null,
				'displayname' => null,
				'quota' => null,
				'subadmin' => array(),
				'email' => null,
				'isRestoreDisabled' => false,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password', array('NewGroup', 'ExistingGroup'));
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
				'message' => 'Unable to create user.'
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->create('foo', 'password', array());
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
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->destroy('myself');
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
			),
			Http::STATUS_NO_CONTENT
		);
		$response = $this->container['UsersController']->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}
	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testDestroyUnsuccessful() {
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
			->will($this->returnValue(false));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));
		$this->container['UserManager']
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($toDeleteUser));

		$expectedResponse = new DataResponse(
			array(
				'status' => 'error',
				'data' => array(
					'message' => 'Unable to delete user.'
				)
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if an invalid mail result in a failure response
	 */
	public function testCreateUnsuccessfulWithInvalidEMail() {
		/**
		 * FIXME: Disabled due to missing DI on mail class.
		 * TODO: Re-enable when https://github.com/owncloud/core/pull/12085 is merged.
		 */
		$this->markTestSkipped('Disable test until OC_Mail is rewritten.');

		$this->container['Mail']
			->expects($this->once())
			->method('validateAddress')
			->will($this->returnValue(false));

		$expectedResponse = new DataResponse(
			array(
				'message' => 'Invalid mail address'
			),
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$response = $this->container['UsersController']->create('foo', 'password', array(), 'invalidMailAdress');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if a valid mail result in a successful mail send
	 */
	public function testCreateSuccessfulWithValidEMail() {
		/**
		 * FIXME: Disabled due to missing DI on mail class.
		 * TODO: Re-enable when https://github.com/owncloud/core/pull/12085 is merged.
		 */
		$this->markTestSkipped('Disable test until OC_Mail is rewritten.');

		$this->container['Mail']
			->expects($this->once())
			->method('validateAddress')
			->will($this->returnValue(true));
		$this->container['Mail']
			->expects($this->once())
			->method('send')
			->with(
				$this->equalTo('validMail@Adre.ss'),
				$this->equalTo('foo'),
				$this->anything(),
				$this->anything(),
				$this->anything(),
				$this->equalTo('no-reply@owncloud.com'),
				$this->equalTo(1),
				$this->anything()
			);
		$this->container['Logger']
			->expects($this->never())
			->method('error');

		$response = $this->container['UsersController']->create('foo', 'password', array(), 'validMail@Adre.ss');
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
	}

	private function mockUser($userId = 'foo', $displayName = 'M. Foo',
							  $lastLogin = 500, $home = '/home/foo', $backend = 'OC_User_Database') {
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue($userId));
		$user
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue($displayName));
		$user
			->method('getLastLogin')
			->will($this->returnValue($lastLogin));
		$user
			->method('getHome')
			->will($this->returnValue($home));
		$user
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue($backend));

		$result = [
			'name' => $userId,
			'displayname' => $displayName,
			'groups' => null,
			'subadmin' => array(),
			'quota' => null,
			'storageLocation' => $home,
			'lastLogin' => $lastLogin,
			'backend' => $backend,
			'email' => null,
			'isRestoreDisabled' => false,
		];

		return [$user, $result];
	}

	public function testRestorePossibleWithoutEncryption() {
		list($user, $expectedResult) = $this->mockUser();

		$result = \Test_Helper::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestorePossibleWithAdminAndUserRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('files_encryption')
			)
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('files_encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->container['Config']
			->expects($this->at(1))
			->method('getUserValue')
			->with(
				$this->anything(),
				$this->equalTo('files_encryption'),
				$this->equalTo('recovery_enabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$result = \Test_Helper::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutAdminRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->method('isEnabledForUser')
			->with(
				$this->equalTo('files_encryption')
			)
			->will($this->returnValue(true));

		$expectedResult['isRestoreDisabled'] = true;

		$result = \Test_Helper::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutUserRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('files_encryption')
			)
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('files_encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->container['Config']
			->expects($this->at(1))
			->method('getUserValue')
			->with(
				$this->anything(),
				$this->equalTo('files_encryption'),
				$this->equalTo('recovery_enabled'),
				$this->anything()
			)
			->will($this->returnValue('0'));

		$expectedResult['isRestoreDisabled'] = true;

		$result = \Test_Helper::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

}
