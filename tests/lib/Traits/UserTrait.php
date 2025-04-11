<?php
/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OC\User\User;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Server;

class DummyUser extends User {
	private string $uid;

	public function __construct(string $uid) {
		$this->uid = $uid;
		parent::__construct($uid, null, Server::get(IEventDispatcher::class));
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
