<?php
/**
 * Copyright (c) 2014 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Migrate extends PHPUnit_Framework_TestCase {

	public $users;

	public function testUserSelfExport(){
		// Create a user
		$user = uniqid();
		$u = new OC_User();
		$u->createUser($user, 'password');
		$this->users[] = $user;
		$exportLocation = \OC_Migrate::export($user);
	}

	public function tearDown() {
		$u = new OC_User();
		foreach($this->users as $user) {
			$u->deleteUser($user);
		}
	}




}
