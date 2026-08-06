<?php

/**
 * SPDX-FileCopyrightText: 2022-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Traits;

use OC\Group\Group;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\Server;
use Test\Util\Group\Dummy;

class DummyGroup extends Group {
	public function __construct(
		private string $gid,
	) {
		parent::__construct(
			$this->gid,
			[],
			Server::get(IEventDispatcher::class),
			Server::get(IUserManager::class),
		);
	}

	#[\Override]
	public function getGID(): string {
		return $this->gid;
	}
}

/**
 * Allow creating users in a temporary backend
 */
trait GroupTrait {
	protected Dummy $groupBackend;

	protected function createGroup(string $name, array $users = []): IGroup {
		$this->groupBackend->createGroup($name);
		foreach ($users as $user) {
			$this->groupBackend->addToGroup($user, $name);
		}
		return new DummyGroup($name);
	}

	protected function addToGroup(string $user, string $group): void {
		$this->groupBackend->addToGroup($user, $group);
	}

	protected function setUpGroupTrait() {
		$this->groupBackend = new Dummy();
		Server::get(IGroupManager::class)->addBackend($this->groupBackend);
	}

	protected function tearDownGroupTrait() {
		Server::get(IGroupManager::class)->removeBackend($this->groupBackend);
	}
}
