<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Cache;

class Permissions extends \PHPUnit_Framework_TestCase {
	/***
	 * @var \OC\Files\Cache\Permissions $permissionsCache
	 */
	private $permissionsCache;

	function setUp(){
		$this->permissionsCache=new \OC\Files\Cache\Permissions('dummy');
	}

	function testSimple() {
		$ids = range(1, 10);
		$user = uniqid();

		$this->assertEquals(-1, $this->permissionsCache->get(1, $user));
		$this->permissionsCache->set(1, $user, 1);
		$this->assertEquals(1, $this->permissionsCache->get(1, $user));
		$this->assertEquals(-1, $this->permissionsCache->get(2, $user));
		$this->assertEquals(-1, $this->permissionsCache->get(1, $user . '2'));

		$this->permissionsCache->set(1, $user, 2);
		$this->assertEquals(2, $this->permissionsCache->get(1, $user));

		$this->permissionsCache->set(2, $user, 1);
		$this->assertEquals(1, $this->permissionsCache->get(2, $user));

		$this->permissionsCache->remove(1, $user);
		$this->assertEquals(-1, $this->permissionsCache->get(1, $user));
		$this->permissionsCache->remove(1, $user . '2');
		$this->assertEquals(1, $this->permissionsCache->get(2, $user));

		$expected = array();
		foreach ($ids as $id) {
			$this->permissionsCache->set($id, $user, 10 + $id);
			$expected[$id] = 10 + $id;
		}
		$this->assertEquals($expected, $this->permissionsCache->getMultiple($ids, $user));

		$this->permissionsCache->removeMultiple(array(10, 9), $user);
		unset($expected[9]);
		unset($expected[10]);
		$this->assertEquals($expected, $this->permissionsCache->getMultiple($ids, $user));

		$this->permissionsCache->removeMultiple($ids, $user);
	}
}
