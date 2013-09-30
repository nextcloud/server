<?php

/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Hooks\PublicEmitter;

class User extends \PHPUnit_Framework_TestCase {

	public function testCheckPassword() {
		/**
		 * @var \OC_User_Backend | \PHPUnit_Framework_MockObject_MockObject $backend
		 */
		$backend = $this->getMock('\OC_User_Dummy');
		$backend->expects($this->once())
			->method('checkPassword')
			->with($this->equalTo('foo'), $this->equalTo('bar'))
			->will($this->returnValue('foo'));

		$backend->expects($this->any())
			->method('implementsActions')
			->will($this->returnCallback(function ($actions) {
				if ($actions === \OC_USER_BACKEND_CHECK_PASSWORD) {
					return true;
				} else {
					return false;
				}
			}));

		$manager = \OC_User::getManager();
		$manager->registerBackend($backend);

		$uid = \OC_User::checkPassword('foo', 'bar');
		$this->assertEquals($uid, 'foo');
	}

}