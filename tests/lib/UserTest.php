<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

/**
 * Class User
 *
 * @group DB
 *
 * @package Test
 */
class UserTest extends TestCase {
	/**
	 * @var \OC\User\Backend | \PHPUnit_Framework_MockObject_MockObject $backend
	 */
	private $backend;
	
	protected function setUp(){
		parent::setUp();

		$this->backend = $this->getMock('\Test\Util\User\Dummy');
		$manager = \OC::$server->getUserManager();
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
				if ($actions === \OC\USER\BACKEND::CHECK_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$uid = \OC_User::checkPassword('foo', 'bar');
		$this->assertEquals($uid, 'foo');
	}

}
