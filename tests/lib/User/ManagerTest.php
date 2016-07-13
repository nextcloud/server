<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

/**
 * Class ManagerTest
 *
 * @group DB
 *
 * @package Test\User
 */
class ManagerTest extends \Test\TestCase {
	public function testGetBackends() {
		$userDummyBackend = $this->getMock('\Test\Util\User\Dummy');
		$manager = new \OC\User\Manager();
		$manager->registerBackend($userDummyBackend);
		$this->assertEquals([$userDummyBackend], $manager->getBackends());
		$dummyDatabaseBackend = $this->getMock('\OC_User_Database');
		$manager->registerBackend($dummyDatabaseBackend);
		$this->assertEquals([$userDummyBackend, $dummyDatabaseBackend], $manager->getBackends());
	}


	public function testUserExistsSingleBackendExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertTrue($manager->userExists('foo'));
	}

	public function testUserExistsSingleBackendNotExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertFalse($manager->userExists('foo'));
	}

	public function testUserExistsNoBackends() {
		$manager = new \OC\User\Manager();

		$this->assertFalse($manager->userExists('foo'));
	}

	public function testUserExistsTwoBackendsSecondExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\Test\Util\User\Dummy');
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\Test\Util\User\Dummy');
		$backend2->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$this->assertTrue($manager->userExists('foo'));
	}

	public function testUserExistsTwoBackendsFirstExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\Test\Util\User\Dummy');
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\Test\Util\User\Dummy');
		$backend2->expects($this->never())
			->method('userExists');

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$this->assertTrue($manager->userExists('foo'));
	}

	public function testCheckPassword() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('checkPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'))
			->will($this->returnValue(true));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC\USER\BACKEND::CHECK_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$user = $manager->checkPassword('foo', 'bar');
		$this->assertTrue($user instanceof \OC\User\User);
	}

	public function testCheckPasswordNotSupported() {
		/**
		 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->never())
			->method('checkPassword');

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertFalse($manager->checkPassword('foo', 'bar'));
	}

	public function testGetOneBackendExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertEquals('foo', $manager->get('foo')->getUID());
	}

	public function testGetOneBackendNotExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertEquals(null, $manager->get('foo'));
	}

	public function testSearchOneBackend() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'))
			->will($this->returnValue(array('foo', 'afoo')));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$result = $manager->search('fo');
		$this->assertEquals(2, count($result));
		$this->assertEquals('afoo', array_shift($result)->getUID());
		$this->assertEquals('foo', array_shift($result)->getUID());
	}

	public function testSearchTwoBackendLimitOffset() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\Test\Util\User\Dummy');
		$backend1->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(3), $this->equalTo(1))
			->will($this->returnValue(array('foo1', 'foo2')));

		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\Test\Util\User\Dummy');
		$backend2->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(3), $this->equalTo(1))
			->will($this->returnValue(array('foo3')));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$result = $manager->search('fo', 3, 1);
		$this->assertEquals(3, count($result));
		$this->assertEquals('foo1', array_shift($result)->getUID());
		$this->assertEquals('foo2', array_shift($result)->getUID());
		$this->assertEquals('foo3', array_shift($result)->getUID());
	}

	public function testCreateUserSingleBackendNotExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->once())
			->method('createUser')
			->with($this->equalTo('foo'), $this->equalTo('bar'));

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$user = $manager->createUser('foo', 'bar');
		$this->assertEquals('foo', $user->getUID());
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCreateUserSingleBackendExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend->expects($this->never())
			->method('createUser');

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$manager->createUser('foo', 'bar');
	}

	public function testCreateUserSingleBackendNotSupported() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(false));

		$backend->expects($this->never())
			->method('createUser');

		$backend->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$this->assertFalse($manager->createUser('foo', 'bar'));
	}

	public function testCreateUserNoBackends() {
		$manager = new \OC\User\Manager();

		$this->assertFalse($manager->createUser('foo', 'bar'));
	}

	/**
	 * @expectedException \Exception
	 */
	public function testCreateUserTwoBackendExists() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\Test\Util\User\Dummy');
		$backend1->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend1->expects($this->never())
			->method('createUser');

		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\Test\Util\User\Dummy');
		$backend2->expects($this->any())
			->method('implementsActions')
			->will($this->returnValue(true));

		$backend2->expects($this->never())
			->method('createUser');

		$backend2->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$manager->createUser('foo', 'bar');
	}

	public function testCountUsersNoBackend() {
		$manager = new \OC\User\Manager();

		$result = $manager->countUsers();
		$this->assertTrue(is_array($result));
		$this->assertTrue(empty($result));
	}

	public function testCountUsersOneBackend() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\Test\Util\User\Dummy');
		$backend->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(7));

		$backend->expects($this->once())
			->method('implementsActions')
			->with(\OC\USER\BACKEND::COUNT_USERS)
			->will($this->returnValue(true));

		$backend->expects($this->once())
			->method('getBackendName')
			->will($this->returnValue('Mock_Test_Util_User_Dummy'));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$result = $manager->countUsers();
		$keys = array_keys($result);
		$this->assertTrue(strpos($keys[0], 'Mock_Test_Util_User_Dummy') !== false);

		$users = array_shift($result);
		$this->assertEquals(7, $users);
	}

	public function testCountUsersTwoBackends() {
		/**
		 * @var \Test\Util\User\Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend1 = $this->getMock('\Test\Util\User\Dummy');
		$backend1->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(7));

		$backend1->expects($this->once())
			->method('implementsActions')
			->with(\OC\USER\BACKEND::COUNT_USERS)
			->will($this->returnValue(true));
		$backend1->expects($this->once())
			->method('getBackendName')
			->will($this->returnValue('Mock_Test_Util_User_Dummy'));

		$backend2 = $this->getMock('\Test\Util\User\Dummy');
		$backend2->expects($this->once())
			->method('countUsers')
			->will($this->returnValue(16));

		$backend2->expects($this->once())
			->method('implementsActions')
			->with(\OC\USER\BACKEND::COUNT_USERS)
			->will($this->returnValue(true));
		$backend2->expects($this->once())
			->method('getBackendName')
			->will($this->returnValue('Mock_Test_Util_User_Dummy'));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$result = $manager->countUsers();
		//because the backends have the same class name, only one value expected
		$this->assertEquals(1, count($result));
		$keys = array_keys($result);
		$this->assertTrue(strpos($keys[0], 'Mock_Test_Util_User_Dummy') !== false);

		$users = array_shift($result);
		//users from backends shall be summed up
		$this->assertEquals(7 + 16, $users);
	}

	public function testDeleteUser() {
		$config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()
			->getMock();
		$config
				->expects($this->at(0))
				->method('getUserValue')
				->with('foo', 'core', 'enabled')
				->will($this->returnValue(true));
		$config
				->expects($this->at(1))
				->method('getUserValue')
				->with('foo', 'login', 'lastLogin')
				->will($this->returnValue(0));

		$manager = new \OC\User\Manager($config);
		$backend = new \Test\Util\User\Dummy();

		$manager->registerBackend($backend);
		$backend->createUser('foo', 'bar');
		$this->assertTrue($manager->userExists('foo'));
		$manager->get('foo')->delete();
		$this->assertFalse($manager->userExists('foo'));
	}
}
