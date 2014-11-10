<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class User extends TestCase {
	/**
	 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
	 */
	private $backend;
	
	protected function setUp(){
		parent::setUp();

		$this->backend = $this->getMock('\OC_User_Dummy');
		$manager = \OC_User::getManager();
		$manager->registerBackend($this->backend);
	}
	
	public function testCheckPassword() {

		$this->backend->expects($this->once())
			->method('checkPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'))
			->will($this->returnValue('foo'))
		;

		$this->backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_CHECK_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$uid = \OC_User::checkPassword('foo', 'bar');
		$this->assertEquals($uid, 'foo');
	}
	
	public function testDeleteUser() {
		$fail = \OC_User::deleteUser('victim');
		$this->assertFalse($fail);
		
		$success = \OC_User::createUser('victim', 'password');
		
		$success = \OC_User::deleteUser('victim');
		$this->assertTrue($success);
	}
	
	public function testCreateUser(){
		$this->backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_CREATE_USER) {
					return true;
				} else {
					return false;
				}
			}));
			
		$user = \OC_User::createUser('newuser', 'newpassword');
		$this->assertEquals('newuser', $user->getUid());
	}

}