<?php
/**
 * @author Lukas Reschke
 * @copyright 2014-2015 Lukas Reschke lukas@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Tests\Settings\Controller;

use \OC\Settings\Application;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;

/**
 * @group DB
 *
 * @package Tests\Settings\Controller
 */
class UsersControllerTest extends \Test\TestCase {

	/** @var \OCP\AppFramework\IAppContainer */
	private $container;

	protected function setUp() {
		$app = new Application();
		$this->container = $app->getContainer();
		$this->container['AppName'] = 'settings';
		$this->container['GroupManager'] = $this->getMockBuilder('\OC\Group\Manager')
			->disableOriginalConstructor()->getMock();
		$this->container['UserManager'] = $this->getMockBuilder('\OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession'] = $this->getMockBuilder('\OC\User\Session')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N'] = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->container['Config'] = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->container['L10N']
			->expects($this->any())
			->method('t')
			->will($this->returnCallback(function($text, $parameters = array()) {
				return vsprintf($text, $parameters);
			}));
		$this->container['Defaults'] = $this->getMockBuilder('\OC_Defaults')
			->disableOriginalConstructor()->getMock();
		$this->container['Mailer'] = $this->getMockBuilder('\OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->container['DefaultMailAddress'] = 'no-reply@owncloud.com';
		$this->container['Logger'] = $this->getMockBuilder('\OCP\ILogger')
			->disableOriginalConstructor()->getMock();
		$this->container['URLGenerator'] = $this->getMockBuilder('\OCP\IURLGenerator')
			->disableOriginalConstructor()->getMock();
		$this->container['OCP\\App\\IAppManager'] = $this->getMockBuilder('OCP\\App\\IAppManager')
			->disableOriginalConstructor()->getMock();


		/*
		 * Set default avtar behaviour for whole testsuite
		 */
		$this->container['OCP\\IAvatarManager'] = $this->getMock('OCP\IAvatarManager');

		$avatarExists = $this->getMock('OCP\IAvatar');
		$avatarExists->method('exists')->willReturn(true);
		$avatarNotExists = $this->getMock('OCP\IAvatar');
		$avatarNotExists->method('exists')->willReturn(false);
		$this->container['OCP\\IAvatarManager']
			->method('getAvatar')
			->will($this->returnValueMap([
				['foo', $avatarExists],
				['bar', $avatarExists],
				['admin', $avatarNotExists],
			]));

		$this->container['Config']
			->method('getSystemValue')
			->with('enable_avatars', true)
			->willReturn(true);

	}

	public function testIndexAdmin() {
		$this->container['IsAdmin'] = true;

		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('foo'));
		$foo
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$foo
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('foo@bar.com'));
		$foo
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('1024'));
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
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$admin
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('S. Admin'));
		$admin
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('admin@bar.com'));
		$admin
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('404'));
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
			->will($this->returnValue('\Test\Util\User\Dummy'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('bar'));
		$bar
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('B. Ar'));
		$bar
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('bar@dummy.com'));
		$bar
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('2323'));
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));
		$bar
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('\Test\Util\User\Dummy'));

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

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->any())
			->method('getSubAdminsGroups')
			->with($foo)
			->will($this->returnValue([]));
		$subadmin
			->expects($this->any())
			->method('getSubAdminsGroups')
			->with($admin)
			->will($this->returnValue([]));
		$subadmin
			->expects($this->any())
			->method('getSubAdminsGroups')
			->with($bar)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => array('Users', 'Support'),
					'subadmin' => array(),
					'quota' => 1024,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500000,
					'backend' => 'OC_User_Database',
					'email' => 'foo@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				),
				1 => array(
					'name' => 'admin',
					'displayname' => 'S. Admin',
					'groups' => array('admins', 'Support'),
					'subadmin' => array(),
					'quota' => 404,
					'storageLocation' => '/home/admin',
					'lastLogin' => 12000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'admin@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => false,
				),
				2 => array(
					'name' => 'bar',
					'displayname' => 'B. Ar',
					'groups' => array('External Users'),
					'subadmin' => array(),
					'quota' => 2323,
					'storageLocation' => '/home/bar',
					'lastLogin' => 3999000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'bar@dummy.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				),
			)
		);
		$response = $this->container['UsersController']->index(0, 10, 'gid', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexSubAdmin() {
		$this->container['IsAdmin'] = false;

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('foo'));
		$foo
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$foo
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('foo@bar.com'));
		$foo
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('1024'));
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
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$admin
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('S. Admin'));
		$admin
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('admin@bar.com'));
		$admin
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('404'));
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
			->will($this->returnValue('\Test\Util\User\Dummy'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('bar'));
		$bar
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('B. Ar'));
		$bar
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('bar@dummy.com'));
		$bar
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('2323'));
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));
		$bar
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('\Test\Util\User\Dummy'));

		$this->container['GroupManager']
			->expects($this->at(2))
			->method('displayNamesInGroup')
			->with('SubGroup2', 'pattern')
			->will($this->returnValue(['foo' => 'M. Foo', 'admin' => 'S. Admin']));
		$this->container['GroupManager']
			->expects($this->at(1))
			->method('displayNamesInGroup')
			->with('SubGroup1', 'pattern')
			->will($this->returnValue(['bar' => 'B. Ar']));
		$this->container['GroupManager']
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(
				['admin', 'SubGroup1', 'testGroup'],
				['SubGroup2', 'SubGroup1'],
				['SubGroup2', 'Foo']
			));
		$this->container['UserManager']
			->expects($this->at(0))
			->method('get')
			->with('bar')
			->will($this->returnValue($bar));
		$this->container['UserManager']
			->expects($this->at(1))
			->method('get')
			->with('foo')
			->will($this->returnValue($foo));
		$this->container['UserManager']
			->expects($this->at(2))
			->method('get')
			->with('admin')
			->will($this->returnValue($admin));

		$subgroup1 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$subgroup1->expects($this->any())
			->method('getGID')
			->will($this->returnValue('SubGroup1'));
		$subgroup2 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$subgroup2->expects($this->any())
			->method('getGID')
			->will($this->returnValue('SubGroup2'));
		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->at(0))
			->method('getSubAdminsGroups')
			->will($this->returnValue([$subgroup1, $subgroup2]));
		$subadmin
			->expects($this->any())
			->method('getSubAdminsGroups')
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				0 => [
					'name' => 'bar',
					'displayname' => 'B. Ar',
					'groups' => ['SubGroup1'],
					'subadmin' => [],
					'quota' => 2323,
					'storageLocation' => '/home/bar',
					'lastLogin' => 3999000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'bar@dummy.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				],
				1=> [
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => ['SubGroup2', 'SubGroup1'],
					'subadmin' => [],
					'quota' => 1024,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500000,
					'backend' => 'OC_User_Database',
					'email' => 'foo@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				],
				2 => [
					'name' => 'admin',
					'displayname' => 'S. Admin',
					'groups' => ['SubGroup2'],
					'subadmin' => [],
					'quota' => 404,
					'storageLocation' => '/home/admin',
					'lastLogin' => 12000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'admin@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => false,
				],
			]
		);

		$response = $this->container['UsersController']->index(0, 10, '', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndexWithSearch() {
		$this->container['IsAdmin'] = true;

		$foo = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$foo
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('foo'));
		$foo
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$foo
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('foo@bar.com'));
		$foo
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('1024'));
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
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('admin'));
		$admin
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('S. Admin'));
		$admin
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('admin@bar.com'));
		$admin
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('404'));
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
			->will($this->returnValue('\Test\Util\User\Dummy'));
		$bar = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$bar
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('bar'));
		$bar
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('B. Ar'));
		$bar
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue('bar@dummy.com'));
		$bar
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('2323'));
		$bar
			->method('getLastLogin')
			->will($this->returnValue(3999));
		$bar
			->method('getHome')
			->will($this->returnValue('/home/bar'));
		$bar
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('\Test\Util\User\Dummy'));

		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('pattern', 10, 0)
			->will($this->returnValue([$foo, $admin, $bar]));
		$this->container['GroupManager']
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->any())
			->method('getSubAdminsGroups')
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => array('Users', 'Support'),
					'subadmin' => array(),
					'quota' => 1024,
					'storageLocation' => '/home/foo',
					'lastLogin' => 500000,
					'backend' => 'OC_User_Database',
					'email' => 'foo@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				),
				1 => array(
					'name' => 'admin',
					'displayname' => 'S. Admin',
					'groups' => array('admins', 'Support'),
					'subadmin' => array(),
					'quota' => 404,
					'storageLocation' => '/home/admin',
					'lastLogin' => 12000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'admin@bar.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => false,
				),
				2 => array(
					'name' => 'bar',
					'displayname' => 'B. Ar',
					'groups' => array('External Users'),
					'subadmin' => array(),
					'quota' => 2323,
					'storageLocation' => '/home/bar',
					'lastLogin' => 3999000,
					'backend' => '\Test\Util\User\Dummy',
					'email' => 'bar@dummy.com',
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				),
			)
		);
		$response = $this->container['UsersController']->index(0, 10, '', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexWithBackend() {
		$this->container['IsAdmin'] = true;

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->exactly(2))
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->once())
			->method('getDisplayName')
			->will($this->returnValue('M. Foo'));
		$user
			->expects($this->once())
			->method('getEMailAddress')
			->will($this->returnValue(null));
		$user
			->expects($this->once())
			->method('getQuota')
			->will($this->returnValue('none'));
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
			->will($this->returnValue([new \Test\Util\User\Dummy(), new \OC\User\Database()]));
		$this->container['UserManager']
			->expects($this->once())
			->method('clearBackends');
		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([$user]));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			array(
				0 => array(
					'name' => 'foo',
					'displayname' => 'M. Foo',
					'groups' => null,
					'subadmin' => array(),
					'quota' => 'none',
					'storageLocation' => '/home/foo',
					'lastLogin' => 500000,
					'backend' => 'OC_User_Database',
					'email' => null,
					'isRestoreDisabled' => false,
					'isAvatarAvailable' => true,
				)
			)
		);
		$response = $this->container['UsersController']->index(0, 10, '','', '\Test\Util\User\Dummy');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexWithBackendNoUser() {
		$this->container['IsAdmin'] = true;

		$this->container['UserManager']
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([new \Test\Util\User\Dummy(), new \OC\User\Database()]));
		$this->container['UserManager']
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$expectedResponse = new DataResponse([]);
		$response = $this->container['UsersController']->index(0, 10, '','', '\Test\Util\User\Dummy');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithoutGroupAdmin() {
		$this->container['IsAdmin'] = true;

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

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->any())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

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
				'isAvatarAvailable' => true,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithoutGroupSubAdmin() {
		$this->container['IsAdmin'] = false;
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$newUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$newUser
			->method('getUID')
			->will($this->returnValue('foo'));
		$newUser
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$newUser
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$newUser
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('bar'));
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$subGroup1 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$subGroup1
			->expects($this->once())
			->method('addUser')
			->with($newUser);
		$subGroup2 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$subGroup2
			->expects($this->once())
			->method('addUser')
			->with($newUser);

		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->returnValue($newUser));
		$this->container['GroupManager']
			->expects($this->exactly(2))
			->method('get')
			->will($this->onConsecutiveCalls($subGroup1, $subGroup2));
		$this->container['GroupManager']
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->onConsecutiveCalls(['SubGroup1', 'SubGroup2']));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->at(0))
			->method('getSubAdminsGroups')
			->will($this->returnValue([$subGroup1, $subGroup2]));
		$subadmin
			->expects($this->at(1))
			->method('getSubAdminsGroups')
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			array(
				'name' => 'foo',
				'groups' => ['SubGroup1', 'SubGroup2'],
				'storageLocation' => '/home/user',
				'backend' => 'bar',
				'lastLogin' => 0,
				'displayname' => null,
				'quota' => null,
				'subadmin' => [],
				'email' => null,
				'isRestoreDisabled' => false,
				'isAvatarAvailable' => true,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithGroupAdmin() {
		$this->container['IsAdmin'] = true;

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

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

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
				'isAvatarAvailable' => true,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password', array('NewGroup', 'ExistingGroup'));
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithGroupSubAdmin() {
		$this->container['IsAdmin'] = false;
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$newUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$newUser
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$newUser
			->method('getHome')
			->will($this->returnValue('/home/user'));
		$newUser
			->method('getUID')
			->will($this->returnValue('foo'));
		$newUser
			->expects($this->once())
			->method('getBackendClassName')
			->will($this->returnValue('bar'));
		$subGroup1 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()->getMock();
		$subGroup1
			->expects($this->any())
			->method('getGID')
			->will($this->returnValue('SubGroup1'));
		$subGroup1
			->expects($this->once())
			->method('addUser')
			->with($user);
		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->returnValue($newUser));
		$this->container['GroupManager']
			->expects($this->at(0))
			->method('get')
			->with('SubGroup1')
			->will($this->returnValue($subGroup1));
		$this->container['GroupManager']
			->expects($this->at(4))
			->method('get')
			->with('SubGroup1')
			->will($this->returnValue($subGroup1));
		$this->container['GroupManager']
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->onConsecutiveCalls(['SubGroup1']));
		$this->container['GroupManager']
			->expects($this->once())
			->method('getUserGroupIds')
			->with($newUser)
			->will($this->onConsecutiveCalls(['SubGroup1']));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->at(1))
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([$subGroup1]));
		$subadmin->expects($this->at(2))
			->method('getSubAdminsGroups')
			->with($newUser)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			array(
				'name' => 'foo',
				'groups' => ['SubGroup1'],
				'storageLocation' => '/home/user',
				'backend' => 'bar',
				'lastLogin' => 0,
				'displayname' => null,
				'quota' => null,
				'subadmin' => [],
				'email' => null,
				'isRestoreDisabled' => false,
				'isAvatarAvailable' => true,
			),
			Http::STATUS_CREATED
		);
		$response = $this->container['UsersController']->create('foo', 'password', ['SubGroup1', 'ExistingGroup']);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateUnsuccessfulAdmin() {
		$this->container['IsAdmin'] = true;

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

	public function testCreateUnsuccessfulSubAdmin() {
		$this->container['IsAdmin'] = false;
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('username'));
		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->container['UserManager']
			->method('createUser')
			->will($this->throwException(new \Exception()));

		$subgroup1 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$subgroup1->expects($this->once())
			->method('getGID')
			->will($this->returnValue('SubGroup1'));
		$subgroup2 = $this->getMockBuilder('\OCP\IGroup')
			->disableOriginalConstructor()
			->getMock();
		$subgroup2->expects($this->once())
			->method('getGID')
			->will($this->returnValue('SubGroup2'));
		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([$subgroup1, $subgroup2]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'message' => 'Unable to create user.'
			],
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroySelfAdmin() {
		$this->container['IsAdmin'] = true;

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

	public function testDestroySelfSubadmin() {
		$this->container['IsAdmin'] = false;

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

	public function testDestroyAdmin() {
		$this->container['IsAdmin'] = true;

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

	public function testDestroySubAdmin() {
		$this->container['IsAdmin'] = false;
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
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

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('isUserAccessible')
			->with($user, $toDeleteUser)
			->will($this->returnValue(true));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'status' => 'success',
				'data' => [
					'username' => 'UserToDelete'
				]
			],
			Http::STATUS_NO_CONTENT
		);
		$response = $this->container['UsersController']->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyUnsuccessfulAdmin() {
		$this->container['IsAdmin'] = true;

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

	public function testDestroyUnsuccessfulSubAdmin() {
		$this->container['IsAdmin'] = false;
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));

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

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('isUserAccessible')
			->with($user, $toDeleteUser)
			->will($this->returnValue(true));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'status' => 'error',
				'data' => [
					'message' => 'Unable to delete user.'
				]
			],
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyNotAccessibleToSubAdmin() {
		$this->container['IsAdmin'] = false;

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));

		$toDeleteUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->container['UserSession']
			->method('getUser')
			->will($this->returnValue($user));
		$this->container['UserManager']
			->method('get')
			->with('UserToDelete')
			->will($this->returnValue($toDeleteUser));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('isUserAccessible')
			->with($user, $toDeleteUser)
			->will($this->returnValue(false));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'status' => 'error',
				'data' => [
					'message' => 'Authentication error'
				]
			],
			Http::STATUS_FORBIDDEN
		);
		$response = $this->container['UsersController']->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if an invalid mail result in a failure response
	 */
	public function testCreateUnsuccessfulWithInvalidEmailAdmin() {
		$this->container['IsAdmin'] = true;

		$expectedResponse = new DataResponse([
				'message' => 'Invalid mail address',
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$response = $this->container['UsersController']->create('foo', 'password', [], 'invalidMailAdress');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if a valid mail result in a successful mail send
	 */
	public function testCreateSuccessfulWithValidEmailAdmin() {
		$this->container['IsAdmin'] = true;
		$message = $this->getMockBuilder('\OC\Mail\Message')
			->disableOriginalConstructor()->getMock();
		$message
			->expects($this->at(0))
			->method('setTo')
			->with(['validMail@Adre.ss' => 'foo']);
		$message
			->expects($this->at(1))
			->method('setSubject')
			->with('Your  account was created');
		$htmlBody = new Http\TemplateResponse(
			'settings',
			'email.new_user',
			[
				'username' => 'foo',
				'url' => '',
			],
			'blank'
		);
		$message
			->expects($this->at(2))
			->method('setHtmlBody')
			->with($htmlBody->render());
		$plainBody = new Http\TemplateResponse(
			'settings',
			'email.new_user_plain_text',
			[
				'username' => 'foo',
				'url' => '',
			],
			'blank'
		);
		$message
			->expects($this->at(3))
			->method('setPlainBody')
			->with($plainBody->render());
		$message
			->expects($this->at(4))
			->method('setFrom')
			->with(['no-reply@owncloud.com' => null]);

		$this->container['Mailer']
			->expects($this->at(0))
			->method('validateMailAddress')
			->with('validMail@Adre.ss')
			->will($this->returnValue(true));
		$this->container['Mailer']
			->expects($this->at(1))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->container['Mailer']
			->expects($this->at(2))
			->method('send')
			->with($message);

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

		$this->container['UserManager']
			->expects($this->once())
			->method('createUser')
			->will($this->onConsecutiveCalls($user));
		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$response = $this->container['UsersController']->create('foo', 'password', [], 'validMail@Adre.ss');
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
			'lastLogin' => $lastLogin * 1000,
			'backend' => $backend,
			'email' => null,
			'isRestoreDisabled' => false,
			'isAvatarAvailable' => true,
		];

		return [$user, $result];
	}

	public function testRestorePossibleWithoutEncryption() {
		$this->container['IsAdmin'] = true;

		list($user, $expectedResult) = $this->mockUser();

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$result = self::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestorePossibleWithAdminAndUserRestore() {
		$this->container['IsAdmin'] = true;

		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('encryption')
			)
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->container['Config']
			->expects($this->at(1))
			->method('getUserValue')
			->with(
				$this->anything(),
				$this->equalTo('encryption'),
				$this->equalTo('recoveryEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$result = self::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutAdminRestore() {
		$this->container['IsAdmin'] = true;

		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->method('isEnabledForUser')
			->with(
				$this->equalTo('encryption')
			)
			->will($this->returnValue(true));

		$expectedResult['isRestoreDisabled'] = true;

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$result = self::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutUserRestore() {
		$this->container['IsAdmin'] = true;

		list($user, $expectedResult) = $this->mockUser();

		$this->container['OCP\\App\\IAppManager']
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('encryption')
			)
			->will($this->returnValue(true));
		$this->container['Config']
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->container['Config']
			->expects($this->at(1))
			->method('getUserValue')
			->with(
				$this->anything(),
				$this->equalTo('encryption'),
				$this->equalTo('recoveryEnabled'),
				$this->anything()
			)
			->will($this->returnValue('0'));

		$expectedResult['isRestoreDisabled'] = true;

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$result = self::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testNoAvatar() {
		$this->container['IsAdmin'] = true;

		list($user, $expectedResult) = $this->mockUser();

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$this->container['OCP\\IAvatarManager']
			->method('getAvatar')
			->will($this->throwException(new \OCP\Files\NotFoundException()));
		$expectedResult['isAvatarAvailable'] = false;

		$result = self::invokePrivate($this->container['UsersController'], 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @return array
	 */
	public function setEmailAddressData() {
		return [
			/* mailAddress,    isValid, expectsUpdate, canChangeDisplayName, responseCode */
			[ '',              true,    true,          true,                 Http::STATUS_OK ],
			[ 'foo@local',     true,    true,          true,                 Http::STATUS_OK],
			[ 'foo@bar@local', false,   false,         true,                 Http::STATUS_UNPROCESSABLE_ENTITY],
			[ 'foo@local',     true,    false,         false,                Http::STATUS_FORBIDDEN],
		];
	}

	/**
	 * @dataProvider setEmailAddressData
	 *
	 * @param string $mailAddress
	 * @param bool $isValid
	 * @param bool $expectsUpdate
	 * @param bool $expectsDelete
	 */
	public function testSetEmailAddress($mailAddress, $isValid, $expectsUpdate, $canChangeDisplayName, $responseCode) {
		$this->container['IsAdmin'] = true;

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('foo'));
		$user
			->expects($this->any())
			->method('canChangeDisplayName')
			->will($this->returnValue($canChangeDisplayName));
		$user
			->expects($expectsUpdate ? $this->once() : $this->never())
			->method('setEMailAddress')
			->with(
				$this->equalTo($mailAddress)
			);

		$this->container['UserSession']
			->expects($this->atLeastOnce())
			->method('getUser')
			->will($this->returnValue($user));
		$this->container['Mailer']
			->expects($this->any())
			->method('validateMailAddress')
			->with($mailAddress)
			->willReturn($isValid);

		if ($isValid) {
			$user->expects($this->atLeastOnce())
				->method('canChangeDisplayName')
				->willReturn(true);

			$this->container['UserManager']
				->expects($this->atLeastOnce())
				->method('get')
				->with('foo')
				->will($this->returnValue($user));
		}

		$response = $this->container['UsersController']->setMailAddress($user->getUID(), $mailAddress);

		$this->assertSame($responseCode, $response->getStatus());
	}

	public function testStatsAdmin() {
		$this->container['IsAdmin'] = true;

		$this->container['UserManager']
			->expects($this->at(0))
			->method('countUsers')
			->will($this->returnValue([128, 44]));

		$expectedResponse = new DataResponse(
			[
				'totalUsers' => 172
			]
		);
		$response = $this->container['UsersController']->stats();
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * Tests that the subadmin stats return unique users, even
	 * when a user appears in several groups.
	 */
	public function testStatsSubAdmin() {
		$this->container['IsAdmin'] = false;

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();

		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$group1 = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()->getMock();
		$group1
			->expects($this->once())
			->method('getUsers')
			->will($this->returnValue(['foo' => 'M. Foo', 'admin' => 'S. Admin']));

		$group2 = $this->getMockBuilder('\OC\Group\Group')
			->disableOriginalConstructor()->getMock();
		$group2
			->expects($this->once())
			->method('getUsers')
			->will($this->returnValue(['bar' => 'B. Ar']));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->expects($this->at(0))
			->method('getSubAdminsGroups')
			->will($this->returnValue([$group1, $group2]));

		$this->container['GroupManager']
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'totalUsers' => 3
			]
		);

		$response = $this->container['UsersController']->stats();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetDisplayNameNull() {
		$user = $this->getMock('\OCP\IUser');
		$user->method('getUID')->willReturn('userName');

		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$expectedResponse = new DataResponse(
			[
				'status' => 'error',
				'data' => [
					'message' => 'Authentication error',
				],
			]
		);
		$response = $this->container['UsersController']->setDisplayName(null, 'displayName');

		$this->assertEquals($expectedResponse, $response);
	}

	public function dataSetDisplayName() {
		$data = [];

		$user1 = $this->getMock('\OCP\IUser');
		$user1->method('getUID')->willReturn('user1');
		$user1->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user1, false, false, true];

		$user1 = $this->getMock('\OCP\IUser');
		$user1->method('getUID')->willReturn('user1');
		$user1->method('canChangeDisplayName')->willReturn(false);
		$data[] = [$user1, $user1, false, false, false];

		$user1 = $this->getMock('\OCP\IUser');
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->getMock('\OCP\IUser');
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, false, false, false];

		$user1 = $this->getMock('\OCP\IUser');
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->getMock('\OCP\IUser');
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, true, false, true];

		$user1 = $this->getMock('\OCP\IUser');
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->getMock('\OCP\IUser');
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, false, true, true];

		return $data;
	}

	/**
	 * @dataProvider dataSetDisplayName
	 */
	public function testSetDisplayName($currentUser, $editUser, $isAdmin, $isSubAdmin, $valid) {
		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->willReturn($currentUser);
		$this->container['UserManager']
			->expects($this->once())
			->method('get')
			->with($editUser->getUID())
			->willReturn($editUser);

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->method('isUserAccessible')
			->with($currentUser, $editUser)
			->willReturn($isSubAdmin);

		$this->container['GroupManager']
			->method('getSubAdmin')
			->willReturn($subadmin);
		$this->container['GroupManager']
			->method('isAdmin')
			->with($currentUser->getUID())
			->willReturn($isAdmin);

		if ($valid === true) {
			$editUser->expects($this->once())
				->method('setDisplayName')
				->with('newDisplayName')
				->willReturn(true);
			$expectedResponse = new DataResponse(
				[
					'status' => 'success',
					'data' => [
						'message' => 'Your full name has been changed.',
						'username' => $editUser->getUID(),
						'displayName' => 'newDisplayName',
					],
				]
			);
		} else {
			$editUser->expects($this->never())->method('setDisplayName');
			$expectedResponse = new DataResponse(
				[
					'status' => 'error',
					'data' => [
						'message' => 'Authentication error',
					],
				]
				);
			}

		$response = $this->container['UsersController']->setDisplayName($editUser->getUID(), 'newDisplayName');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetDisplayNameFails() {
		$user = $this->getMock('\OCP\IUser');
		$user->method('canChangeDisplayname')->willReturn(true);
		$user->method('getUID')->willReturn('user');
		$user->expects($this->once())
			->method('setDisplayName')
			->with('newDisplayName')
			->willReturn(false);
		$user->method('getDisplayName')->willReturn('oldDisplayName');

		$this->container['UserSession']
			->expects($this->once())
			->method('getUser')
			->willReturn($user);
		$this->container['UserManager']
			->expects($this->once())
			->method('get')
			->with($user->getUID())
			->willReturn($user);

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->method('isUserAccessible')
			->with($user, $user)
			->willReturn(false);

		$this->container['GroupManager']
			->method('getSubAdmin')
			->willReturn($subadmin);
		$this->container['GroupManager']
			->expects($this->once())
			->method('isAdmin')
			->with($user->getUID())
			->willReturn(false);

		$expectedResponse = new DataResponse(
			[
				'status' => 'error',
				'data' => [
					'message' => 'Unable to change full name',
					'displayName' => 'oldDisplayName',
				],
			]
		);
		$response = $this->container['UsersController']->setDisplayName($user->getUID(), 'newDisplayName');
		$this->assertEquals($expectedResponse, $response);
	}
}
