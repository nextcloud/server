<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\User;

/**
 * Abstract class to provide the basis of backend-specific unit test classes.
 *
 * All subclasses MUST assign a backend property in setUp() which implements
 * user operations (add, remove, etc.). Test methods in this class will then be
 * run on each separate subclass and backend therein.
 *
 * For an example see /tests/lib/user/dummy.php
 */

abstract class Backend extends \Test\TestCase {
	/**
	 * @var \OC\User\Backend $backend
	 */
	protected $backend;

	/**
	 * get a new unique user name
	 * test cases can override this in order to clean up created user
	 * @return string
	 */
	public function getUser() {
		return $this->getUniqueID('test_');
	}

	public function testAddRemove(): void {
		//get the number of groups we start with, in case there are exising groups
		$startCount = count($this->backend->getUsers());

		$name1 = $this->getUser();
		$name2 = $this->getUser();
		$this->backend->createUser($name1, '');
		$count = count($this->backend->getUsers()) - $startCount;
		$this->assertEquals(1, $count);
		$this->assertTrue((array_search($name1, $this->backend->getUsers()) !== false));
		$this->assertFalse((array_search($name2, $this->backend->getUsers()) !== false));
		$this->backend->createUser($name2, '');
		$count = count($this->backend->getUsers()) - $startCount;
		$this->assertEquals(2, $count);
		$this->assertTrue((array_search($name1, $this->backend->getUsers()) !== false));
		$this->assertTrue((array_search($name2, $this->backend->getUsers()) !== false));

		$this->backend->deleteUser($name2);
		$count = count($this->backend->getUsers()) - $startCount;
		$this->assertEquals(1, $count);
		$this->assertTrue((array_search($name1, $this->backend->getUsers()) !== false));
		$this->assertFalse((array_search($name2, $this->backend->getUsers()) !== false));
	}

	public function testLogin(): void {
		$name1 = $this->getUser();
		$name2 = $this->getUser();

		$this->assertFalse($this->backend->userExists($name1));
		$this->assertFalse($this->backend->userExists($name2));

		$this->backend->createUser($name1, 'pass1');
		$this->backend->createUser($name2, 'pass2');

		$this->assertTrue($this->backend->userExists($name1));
		$this->assertTrue($this->backend->userExists($name2));

		$this->assertSame($name1, $this->backend->checkPassword($name1, 'pass1'));
		$this->assertSame($name2, $this->backend->checkPassword($name2, 'pass2'));

		$this->assertFalse($this->backend->checkPassword($name1, 'pass2'));
		$this->assertFalse($this->backend->checkPassword($name2, 'pass1'));

		$this->assertFalse($this->backend->checkPassword($name1, 'dummy'));
		$this->assertFalse($this->backend->checkPassword($name2, 'foobar'));

		$this->backend->setPassword($name1, 'newpass1');
		$this->assertFalse($this->backend->checkPassword($name1, 'pass1'));
		$this->assertSame($name1, $this->backend->checkPassword($name1, 'newpass1'));
		$this->assertFalse($this->backend->checkPassword($name2, 'newpass1'));
	}

	public function testSearch(): void {
		$name1 = 'foobarbaz';
		$name2 = 'bazbarfoo';
		$name3 = 'notme';
		$name4 = 'under_score';

		$this->backend->createUser($name1, 'pass1');
		$this->backend->createUser($name2, 'pass2');
		$this->backend->createUser($name3, 'pass3');
		$this->backend->createUser($name4, 'pass4');

		$result = $this->backend->getUsers('bar');
		$this->assertCount(2, $result);

		$result = $this->backend->getDisplayNames('bar');
		$this->assertCount(2, $result);

		$result = $this->backend->getUsers('under_');
		$this->assertCount(1, $result);

		$result = $this->backend->getUsers('not_');
		$this->assertCount(0, $result);
	}
}
