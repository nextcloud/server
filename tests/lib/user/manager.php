<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\User;

class Manager extends \PHPUnit_Framework_TestCase {
	public function testUserExistsSingleBackendExists() {
		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\OC_User_Dummy');
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(false));

		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\OC_User_Dummy');
		$backend1->expects($this->once())
			->method('userExists')
			->with($this->equalTo('foo'))
			->will($this->returnValue(true));

		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\OC_User_Dummy');
		$backend2->expects($this->never())
			->method('userExists');

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$this->assertTrue($manager->userExists('foo'));
	}

	public function testGetOneBackendExists() {
		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
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
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'))
			->will($this->returnValue(array('foo', 'afoo')));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend);

		$result = $manager->search('fo');
		$this->assertEquals(2, count($result));
		$this->assertEquals('afoo', $result[0]->getUID());
		$this->assertEquals('foo', $result[1]->getUID());
	}

	public function testSearchTwoBackendLimitOffset() {
		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend1
		 */
		$backend1 = $this->getMock('\OC_User_Dummy');
		$backend1->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(3), $this->equalTo(1))
			->will($this->returnValue(array('foo1', 'foo2')));

		/**
		 * @var \OC_User_Dummy | \PHPUnit_Framework_MockObject_MockObject $backend2
		 */
		$backend2 = $this->getMock('\OC_User_Dummy');
		$backend2->expects($this->once())
			->method('getUsers')
			->with($this->equalTo('fo'), $this->equalTo(1), $this->equalTo(0))
			->will($this->returnValue(array('foo3')));

		$manager = new \OC\User\Manager();
		$manager->registerBackend($backend1);
		$manager->registerBackend($backend2);

		$result = $manager->search('fo', 3, 1);
		$this->assertEquals(3, count($result));
		$this->assertEquals('foo1', $result[0]->getUID());
		$this->assertEquals('foo2', $result[1]->getUID());
		$this->assertEquals('foo3', $result[2]->getUID());
	}
}
