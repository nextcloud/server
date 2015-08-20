<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Traits;

/**
 * Allow creating users in a temporary backend
 */
trait UserTrait {
	/**
	 * @var \OC_User_Dummy|\OCP\UserInterface
	 */
	protected $userBackend;

	protected function createUser($name, $password) {
		$this->userBackend->createUser($name, $password);
	}

	protected function setUpUserTrait() {
		$this->userBackend = new \OC_User_Dummy();
		\OC::$server->getUserManager()->registerBackend($this->userBackend);
	}

	protected function tearDownUserTrait() {
		\OC::$server->getUserManager()->removeBackend($this->userBackend);
	}
}
