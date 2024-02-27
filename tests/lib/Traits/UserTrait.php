<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Traits;

use OC\User\User;
use OCP\IUser;

class DummyUser extends User {
	private string $uid;

	public function __construct(string $uid) {
		$this->uid = $uid;
	}

	public function getUID(): string {
		return $this->uid;
	}
}

/**
 * Allow creating users in a temporary backend
 */
trait UserTrait {
	/**
	 * @var \Test\Util\User\Dummy|\OCP\UserInterface
	 */
	protected $userBackend;

	protected function createUser($name, $password): IUser {
		$this->userBackend->createUser($name, $password);
		return new DummyUser($name);
	}

	protected function setUpUserTrait() {
		$this->userBackend = new \Test\Util\User\Dummy();
		\OC::$server->getUserManager()->registerBackend($this->userBackend);
	}

	protected function tearDownUserTrait() {
		\OC::$server->getUserManager()->removeBackend($this->userBackend);
	}
}
