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

use OC\Accounts\AccountManager;
use OC\Group\Manager;
use OC\Settings\Controller\UsersController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Mail\IMailer;

/**
 * @group DB
 *
 * @package Tests\Settings\Controller
 */
class UsersControllerTest extends \Test\TestCase {

	/** @var IGroupManager|\PHPUnit_Framework_MockObject_MockObject */
	private $groupManager;
	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IUserSession|\PHPUnit_Framework_MockObject_MockObject */
	private $userSession;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var \OC_Defaults|\PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var IMailer|\PHPUnit_Framework_MockObject_MockObject */
	private $mailer;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;
	/** @var IAppManager|\PHPUnit_Framework_MockObject_MockObject */
	private $appManager;
	/** @var IAvatarManager|\PHPUnit_Framework_MockObject_MockObject */
	private $avatarManager;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l;
	/** @var  AccountManager | \PHPUnit_Framework_MockObject_MockObject */
	private $accountManager;

	protected function setUp() {
		parent::setUp();

		$this->groupManager = $this->createMock(Manager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->defaults = $this->createMock(\OC_Defaults::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->avatarManager = $this->createMock(IAvatarManager::class);
		$this->accountManager = $this->createMock(AccountManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')
			->will($this->returnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			}));

		/*
		 * Set default avatar behaviour for whole test suite
		 */

		$avatarExists = $this->createMock(IAvatar::class);
		$avatarExists->method('exists')->willReturn(true);
		$avatarNotExists = $this->createMock(IAvatar::class);
		$avatarNotExists->method('exists')->willReturn(false);
		$this->avatarManager->method('getAvatar')
			->will($this->returnValueMap([
				['foo', $avatarExists],
				['bar', $avatarExists],
				['admin', $avatarNotExists],
			]));

		$this->config->method('getSystemValue')
			->with('enable_avatars', true)
			->willReturn(true);
	}

	/**
	 * @param bool $isAdmin
	 * @return UsersController | \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getController($isAdmin = false, $mockedMethods = []) {
		if (empty($mockedMethods)) {
			return new UsersController(
				'settings',
				$this->createMock(IRequest::class),
				$this->userManager,
				$this->groupManager,
				$this->userSession,
				$this->config,
				$isAdmin,
				$this->l,
				$this->logger,
				$this->defaults,
				$this->mailer,
				'no-reply@owncloud.com',
				$this->urlGenerator,
				$this->appManager,
				$this->avatarManager,
				$this->accountManager
			);
		} else {
			return $this->getMockBuilder(UsersController::class)
				->setConstructorArgs(
					[
						'settings',
						$this->createMock(IRequest::class),
						$this->userManager,
						$this->groupManager,
						$this->userSession,
						$this->config,
						$isAdmin,
						$this->l,
						$this->logger,
						$this->defaults,
						$this->mailer,
						'no-reply@owncloud.com',
						$this->urlGenerator,
						$this->appManager,
						$this->avatarManager,
						$this->accountManager
					]
				)->setMethods($mockedMethods)->getMock();
		}
	}

	public function testIndexAdmin() {
		$controller = $this->getController(true);

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

		$this->groupManager
			->expects($this->once())
			->method('displayNamesInGroup')
			->with('gid', 'pattern')
			->will($this->returnValue(array('foo' => 'M. Foo', 'admin' => 'S. Admin', 'bar' => 'B. Ar')));
		$this->groupManager
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));
		$this->userManager
			->expects($this->at(0))
			->method('get')
			->with('foo')
			->will($this->returnValue($foo));
		$this->userManager
			->expects($this->at(1))
			->method('get')
			->with('admin')
			->will($this->returnValue($admin));
		$this->userManager
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
		$this->groupManager
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
		$response = $controller->index(0, 10, 'gid', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexSubAdmin() {
		$controller = $this->getController(false);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->userSession
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

		$this->groupManager
			->expects($this->at(2))
			->method('displayNamesInGroup')
			->with('SubGroup2', 'pattern')
			->will($this->returnValue(['foo' => 'M. Foo', 'admin' => 'S. Admin']));
		$this->groupManager
			->expects($this->at(1))
			->method('displayNamesInGroup')
			->with('SubGroup1', 'pattern')
			->will($this->returnValue(['bar' => 'B. Ar']));
		$this->groupManager
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(
				['admin', 'SubGroup1', 'testGroup'],
				['SubGroup2', 'SubGroup1'],
				['SubGroup2', 'Foo']
			));
		$this->userManager
			->expects($this->at(0))
			->method('get')
			->with('bar')
			->will($this->returnValue($bar));
		$this->userManager
			->expects($this->at(1))
			->method('get')
			->with('foo')
			->will($this->returnValue($foo));
		$this->userManager
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
		$this->groupManager
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

		$response = $controller->index(0, 10, '', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * TODO: Since the function uses the static OC_Subadmin class it can't be mocked
	 * to test for subadmins. Thus the test always assumes you have admin permissions...
	 */
	public function testIndexWithSearch() {
		$controller = $this->getController(true);

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

		$this->userManager
			->expects($this->once())
			->method('search')
			->with('pattern', 10, 0)
			->will($this->returnValue([$foo, $admin, $bar]));
		$this->groupManager
			->expects($this->exactly(3))
			->method('getUserGroupIds')
			->will($this->onConsecutiveCalls(array('Users', 'Support'), array('admins', 'Support'), array('External Users')));

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->any())
			->method('getSubAdminsGroups')
			->will($this->returnValue([]));
		$this->groupManager
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
		$response = $controller->index(0, 10, '', 'pattern');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexWithBackend() {
		$controller = $this->getController(true);

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
		$this->userManager
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([new \Test\Util\User\Dummy(), new \OC\User\Database()]));
		$this->userManager
			->expects($this->once())
			->method('clearBackends');
		$this->userManager
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
		$this->groupManager
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
		$response = $controller->index(0, 10, '','', '\Test\Util\User\Dummy');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testIndexWithBackendNoUser() {
		$controller = $this->getController(true);

		$this->userManager
			->expects($this->once())
			->method('getBackends')
			->will($this->returnValue([new \Test\Util\User\Dummy(), new \OC\User\Database()]));
		$this->userManager
			->expects($this->once())
			->method('search')
			->with('')
			->will($this->returnValue([]));

		$expectedResponse = new DataResponse([]);
		$response = $controller->index(0, 10, '','', '\Test\Util\User\Dummy');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithoutGroupAdmin() {
		$controller = $this->getController(true);

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

		$this->userManager
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
		$this->groupManager
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
		$response = $controller->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithoutGroupSubAdmin() {
		$controller = $this->getController(false);
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->userSession
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

		$this->userManager
			->expects($this->once())
			->method('createUser')
			->will($this->returnValue($newUser));
		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->will($this->onConsecutiveCalls($subGroup1, $subGroup2));
		$this->groupManager
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
		$this->groupManager
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
		$response = $controller->create('foo', 'password');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithGroupAdmin() {
		$controller = $this->getController(true);

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

		$this->userManager
			->expects($this->once())
			->method('createUser')
			->will($this->onConsecutiveCalls($user));
		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->will($this->onConsecutiveCalls(null, $existingGroup));
		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with('NewGroup')
			->will($this->onConsecutiveCalls($newGroup));
		$this->groupManager
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
		$this->groupManager
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
		$response = $controller->create('foo', 'password', array('NewGroup', 'ExistingGroup'));
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateSuccessfulWithGroupSubAdmin() {
		$controller = $this->getController(false);
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->userSession
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
		$this->userManager
			->expects($this->once())
			->method('createUser')
			->will($this->returnValue($newUser));
		$this->groupManager
			->expects($this->at(0))
			->method('get')
			->with('SubGroup1')
			->will($this->returnValue($subGroup1));
		$this->groupManager
			->expects($this->at(4))
			->method('get')
			->with('SubGroup1')
			->will($this->returnValue($subGroup1));
		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->will($this->onConsecutiveCalls(['SubGroup1']));
		$this->groupManager
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
		$this->groupManager
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
		$response = $controller->create('foo', 'password', ['SubGroup1', 'ExistingGroup']);
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateUnsuccessfulAdmin() {
		$controller = $this->getController(true);

		$this->userManager
			->method('createUser')
			->will($this->throwException(new \Exception()));

		$expectedResponse = new DataResponse(
			array(
				'message' => 'Unable to create user.'
			),
			Http::STATUS_FORBIDDEN
		);
		$response = $controller->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	public function testCreateUnsuccessfulSubAdmin() {
		$controller = $this->getController(false);
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->any())
			->method('getUID')
			->will($this->returnValue('username'));
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->will($this->returnValue($user));

		$this->userManager
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
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'message' => 'Unable to create user.'
			],
			Http::STATUS_FORBIDDEN
		);
		$response = $controller->create('foo', 'password', array());
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroySelfAdmin() {
		$controller = $this->getController(true);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->userSession
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
		$response = $controller->destroy('myself');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroySelfSubadmin() {
		$controller = $this->getController(false);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->userSession
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
		$response = $controller->destroy('myself');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyAdmin() {
		$controller = $this->getController(true);

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
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));
		$this->userManager
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
		$response = $controller->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroySubAdmin() {
		$controller = $this->getController(false);
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->userSession
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
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));
		$this->userManager
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
		$this->groupManager
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
		$response = $controller->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyUnsuccessfulAdmin() {
		$controller = $this->getController(true);

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
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));
		$this->userManager
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
		$response = $controller->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyUnsuccessfulSubAdmin() {
		$controller = $this->getController(false);
		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));

		$toDeleteUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$toDeleteUser
			->expects($this->once())
			->method('delete')
			->will($this->returnValue(false));
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));
		$this->userManager
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
		$this->groupManager
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
		$response = $controller->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testDestroyNotAccessibleToSubAdmin() {
		$controller = $this->getController(false);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$user
			->expects($this->once())
			->method('getUID')
			->will($this->returnValue('myself'));
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));

		$toDeleteUser = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();
		$this->userSession
			->method('getUser')
			->will($this->returnValue($user));
		$this->userManager
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
		$this->groupManager
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
		$response = $controller->destroy('UserToDelete');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if an invalid mail result in a failure response
	 */
	public function testCreateUnsuccessfulWithInvalidEmailAdmin() {
		$controller = $this->getController(true);

		$expectedResponse = new DataResponse([
				'message' => 'Invalid mail address',
			],
			Http::STATUS_UNPROCESSABLE_ENTITY
		);
		$response = $controller->create('foo', 'password', [], 'invalidMailAdress');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * test if a valid mail result in a successful mail send
	 */
	public function testCreateSuccessfulWithValidEmailAdmin() {
		$controller = $this->getController(true);
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

		$this->mailer
			->expects($this->at(0))
			->method('validateMailAddress')
			->with('validMail@Adre.ss')
			->will($this->returnValue(true));
		$this->mailer
			->expects($this->at(1))
			->method('createMessage')
			->will($this->returnValue($message));
		$this->mailer
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

		$this->userManager
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
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$response = $controller->create('foo', 'password', [], 'validMail@Adre.ss');
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
		$controller = $this->getController(true);

		list($user, $expectedResult) = $this->mockUser();

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$result = self::invokePrivate($controller, 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestorePossibleWithAdminAndUserRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('encryption')
			)
			->will($this->returnValue(true));
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->config
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
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$controller = $this->getController(true);
		$result = self::invokePrivate($controller, 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutAdminRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->appManager
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
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$controller = $this->getController(true);
		$result = self::invokePrivate($controller, 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testRestoreNotPossibleWithoutUserRestore() {
		list($user, $expectedResult) = $this->mockUser();

		$this->appManager
			->expects($this->once())
			->method('isEnabledForUser')
			->with(
				$this->equalTo('encryption')
			)
			->will($this->returnValue(true));
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with(
				$this->equalTo('encryption'),
				$this->equalTo('recoveryAdminEnabled'),
				$this->anything()
			)
			->will($this->returnValue('1'));

		$this->config
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
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$controller = $this->getController(true);
		$result = self::invokePrivate($controller, 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testNoAvatar() {
		$controller = $this->getController(true);

		list($user, $expectedResult) = $this->mockUser();

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin->expects($this->once())
			->method('getSubAdminsGroups')
			->with($user)
			->will($this->returnValue([]));
		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$this->avatarManager
			->method('getAvatar')
			->will($this->throwException(new \OCP\Files\NotFoundException()));
		$expectedResult['isAvatarAvailable'] = false;

		$result = self::invokePrivate($controller, 'formatUserForIndex', [$user]);
		$this->assertEquals($expectedResult, $result);
	}

	public function testStatsAdmin() {
		$controller = $this->getController(true);

		$this->userManager
			->expects($this->at(0))
			->method('countUsers')
			->will($this->returnValue([128, 44]));

		$expectedResponse = new DataResponse(
			[
				'totalUsers' => 172
			]
		);
		$response = $controller->stats();
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * Tests that the subadmin stats return unique users, even
	 * when a user appears in several groups.
	 */
	public function testStatsSubAdmin() {
		$controller = $this->getController(false);

		$user = $this->getMockBuilder('\OC\User\User')
			->disableOriginalConstructor()->getMock();

		$this->userSession
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

		$this->groupManager
			->expects($this->any())
			->method('getSubAdmin')
			->will($this->returnValue($subadmin));

		$expectedResponse = new DataResponse(
			[
				'totalUsers' => 3
			]
		);

		$response = $controller->stats();
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetDisplayNameNull() {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('userName');

		$this->userSession
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
		$controller = $this->getController(true);
		$response = $controller->setDisplayName(null, 'displayName');

		$this->assertEquals($expectedResponse, $response);
	}

	public function dataSetDisplayName() {
		$data = [];

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user1->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user1, false, false, true];

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user1->method('canChangeDisplayName')->willReturn(false);
		$data[] = [$user1, $user1, false, false, false];

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, false, false, false];

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, true, false, true];

		$user1 = $this->createMock(IUser::class);
		$user1->method('getUID')->willReturn('user1');
		$user2 = $this->createMock(IUser::class);
		$user2->method('getUID')->willReturn('user2');
		$user2->method('canChangeDisplayName')->willReturn(true);
		$data[] = [$user1, $user2, false, true, true];

		return $data;
	}

	/**
	 * @dataProvider dataSetDisplayName
	 *
	 * @param IUser|\PHPUnit_Framework_MockObject_MockObject $currentUser
	 * @param IUser|\PHPUnit_Framework_MockObject_MockObject $editUser
	 * @param bool $isAdmin
	 * @param bool $isSubAdmin
	 * @param bool $valid
	 */
	public function testSetDisplayName($currentUser, $editUser, $isAdmin, $isSubAdmin, $valid) {
		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($currentUser);
		$this->userManager
			->expects($this->once())
			->method('get')
			->with($editUser->getUID())
			->willReturn($editUser);
		$this->accountManager->expects($this->any())->method('getUser')->willReturn([]);

		$subadmin = $this->getMockBuilder('\OC\SubAdmin')
			->disableOriginalConstructor()
			->getMock();
		$subadmin
			->method('isUserAccessible')
			->with($currentUser, $editUser)
			->willReturn($isSubAdmin);

		$this->groupManager
			->method('getSubAdmin')
			->willReturn($subadmin);
		$this->groupManager
			->method('isAdmin')
			->with($currentUser->getUID())
			->willReturn($isAdmin);

		if ($valid === true) {
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
			$expectedResponse = new DataResponse(
				[
					'status' => 'error',
					'data' => [
						'message' => 'Authentication error',
					],
				]
				);
			}

		$controller = $this->getController(true);
		$response = $controller->setDisplayName($editUser->getUID(), 'newDisplayName');
		$this->assertEquals($expectedResponse, $response);
	}

	public function testSetDisplayNameFails() {
		/** @var IUser|\PHPUnit_Framework_MockObject_MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->method('canChangeDisplayname')->willReturn(true);
		$user->method('getUID')->willReturn('user');
		$user->expects($this->once())
			->method('setDisplayName')
			->with('newDisplayName')
			->willReturn(false);
		$user->method('getDisplayName')->willReturn('oldDisplayName');

		$this->userSession
			->expects($this->once())
			->method('getUser')
			->willReturn($user);

		$this->userManager
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

		$this->groupManager
			->method('getSubAdmin')
			->willReturn($subadmin);
		$this->groupManager
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
		$controller = $this->getController(true);
		$response = $controller->setDisplayName($user->getUID(), 'newDisplayName');
		$this->assertEquals($expectedResponse, $response);
	}

	/**
	 * @dataProvider dataTestSetUserSettings
	 *
	 * @param string $email
	 * @param bool $validEmail
	 * @param $expectedStatus
	 */
	public function testSetUserSettings($email, $validEmail, $expectedStatus) {
		$controller = $this->getController(false, ['saveUserSettings']);
		$user = $this->createMock(IUser::class);

		$this->userSession->method('getUser')->willReturn($user);

		if (!empty($email) && $validEmail) {
			$this->mailer->expects($this->once())->method('validateMailAddress')
				->willReturn($validEmail);
		}

		$saveData = (!empty($email) && $validEmail) || empty($email);

		if ($saveData) {
			$controller->expects($this->once())->method('saveUserSettings');
		} else {
			$controller->expects($this->never())->method('saveUserSettings');
		}

		$result = $controller->setUserSettings(
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			'displayName',
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			'47658468',
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			$email,
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			'nextcloud.com',
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			'street and city',
			AccountManager::VISIBILITY_CONTACTS_ONLY,
			'@nextclouders',
			AccountManager::VISIBILITY_CONTACTS_ONLY
		);

		$this->assertSame($expectedStatus, $result->getStatus());
	}

	public function dataTestSetUserSettings() {
		return [
			['', true, Http::STATUS_OK],
			['', false, Http::STATUS_OK],
			['example.com', false, Http::STATUS_UNPROCESSABLE_ENTITY],
			['john@example.com', true, Http::STATUS_OK],
		];
	}

	/**
	 * @dataProvider dataTestSaveUserSettings
	 *
	 * @param array $data
	 * @param string $oldEmailAddress
	 * @param string $oldDisplayName
	 */
	public function testSaveUserSettings($data,
										 $oldEmailAddress,
										 $oldDisplayName
	) {
		$controller = $this->getController();
		$user = $this->createMock(IUser::class);

		$user->method('getDisplayName')->willReturn($oldDisplayName);
		$user->method('getEMailAddress')->willReturn($oldEmailAddress);
		$user->method('canChangeDisplayName')->willReturn(true);

		if ($data[AccountManager::PROPERTY_EMAIL]['value'] === $oldEmailAddress ||
		$oldEmailAddress === null && $data[AccountManager::PROPERTY_EMAIL]['value'] === '') {
			$user->expects($this->never())->method('setEMailAddress');
		} else {
			$user->expects($this->once())->method('setEMailAddress')
				->with($data[AccountManager::PROPERTY_EMAIL]['value'])
				->willReturn(true);
		}

		if ($data[AccountManager::PROPERTY_DISPLAYNAME]['value'] === $oldDisplayName ||
			$oldDisplayName === null && $data[AccountManager::PROPERTY_DISPLAYNAME]['value'] === '') {
			$user->expects($this->never())->method('setDisplayName');
		} else {
			$user->expects($this->once())->method('setDisplayName')
				->with($data[AccountManager::PROPERTY_DISPLAYNAME]['value'])
				->willReturn(true);
		}

		$this->accountManager->expects($this->once())->method('updateUser')
			->with($user, $data);

		$this->invokePrivate($controller, 'saveUserSettings', [$user, $data]);
	}

	public function dataTestSaveUserSettings() {
		return [
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				'john doe'
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe'
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john doe'
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				'john New doe'
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => ''],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				null,
				'john New doe'
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				null
			],

		];
	}

	/**
	 * @dataProvider dataTestSaveUserSettingsException
	 *
	 * @param array $data
	 * @param string $oldEmailAddress
	 * @param string $oldDisplayName
	 * @param bool $setDisplayNameResult
	 * @param bool $canChangeEmail
	 *
	 * @expectedException \OC\ForbiddenException
	 */
	public function testSaveUserSettingsException($data,
												  $oldEmailAddress,
												  $oldDisplayName,
												  $setDisplayNameResult,
												  $canChangeEmail
	) {
		$controller = $this->getController();
		$user = $this->createMock(IUser::class);

		$user->method('getDisplayName')->willReturn($oldDisplayName);
		$user->method('getEMailAddress')->willReturn($oldEmailAddress);

		if ($data[AccountManager::PROPERTY_EMAIL]['value'] !== $oldEmailAddress) {
			$user->method('canChangeDisplayName')
				->willReturn($canChangeEmail);
		}

		if ($data[AccountManager::PROPERTY_DISPLAYNAME]['value'] !== $oldDisplayName) {
			$user->method('setDisplayName')
				->with($data[AccountManager::PROPERTY_DISPLAYNAME]['value'])
				->willReturn($setDisplayNameResult);
		}

		$this->invokePrivate($controller, 'saveUserSettings', [$user, $data]);
	}


	public function dataTestSaveUserSettingsException() {
		return [
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				true,
				false
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				false,
				true
			],
			[
				[
					AccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					AccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				false,
				false
			],

		];
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
	 */
	public function testSetEMailAddress($mailAddress, $isValid, $expectsUpdate, $canChangeDisplayName, $responseCode) {
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
		$user->method('getEMailAddress')->willReturn('oldEmailAddress');
		$this->mailer
			->expects($this->any())
			->method('validateMailAddress')
			->with($mailAddress)
			->willReturn($isValid);
		if ($isValid) {
			$user->expects($this->atLeastOnce())
				->method('canChangeDisplayName')
				->willReturn(true);
			$this->userManager
				->expects($this->atLeastOnce())
				->method('get')
				->with('foo')
				->will($this->returnValue($user));
		}
		$controller = $this->getController(true);
		$response = $controller->setEMailAddress($user->getUID(), $mailAddress);
		$this->assertSame($responseCode, $response->getStatus());
	}
}
