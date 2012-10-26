<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Permissions extends \PHPUnit_Framework_TestCase {
	function testSimple() {
		$ids = range(1, 10);
		$user = uniqid();

		$this->assertEquals(-1, \OC\Files\Cache\Permissions::get(1, $user));
		\OC\Files\Cache\Permissions::set(1, $user, 1);
		$this->assertEquals(1, \OC\Files\Cache\Permissions::get(1, $user));
		$this->assertEquals(-1, \OC\Files\Cache\Permissions::get(2, $user));
		$this->assertEquals(-1, \OC\Files\Cache\Permissions::get(1, $user . '2'));

		\OC\Files\Cache\Permissions::set(1, $user, 2);
		$this->assertEquals(2, \OC\Files\Cache\Permissions::get(1, $user));

		\OC\Files\Cache\Permissions::set(2, $user, 1);
		$this->assertEquals(1, \OC\Files\Cache\Permissions::get(2, $user));

		\OC\Files\Cache\Permissions::remove(1, $user);
		$this->assertEquals(-1, \OC\Files\Cache\Permissions::get(1, $user));
		\OC\Files\Cache\Permissions::remove(1, $user . '2');
		$this->assertEquals(1, \OC\Files\Cache\Permissions::get(2, $user));

		$expected = array();
		foreach ($ids as $id) {
			\OC\Files\Cache\Permissions::set($id, $user, 10 + $id);
			$expected[$id] = 10 + $id;
		}
		$this->assertEquals($expected, \OC\Files\Cache\Permissions::getMultiple($ids, $user));

		\OC\Files\Cache\Permissions::removeMultiple(array(10, 9), $user);
		unset($expected[9]);
		unset($expected[10]);
		$this->assertEquals($expected, \OC\Files\Cache\Permissions::getMultiple($ids, $user));

		\OC\Files\Cache\Permissions::removeMultiple($ids, $user);
	}
}
