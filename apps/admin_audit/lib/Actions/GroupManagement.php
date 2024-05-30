<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCP\IGroup;
use OCP\IUser;

/**
 * Class GroupManagement logs all group manager related events
 *
 * @package OCA\AdminAudit\Actions
 */
class GroupManagement extends Action {

	/**
	 * log add user to group event
	 *
	 * @param IGroup $group
	 * @param IUser $user
	 */
	public function addUser(IGroup $group, IUser $user): void {
		$this->log('User "%s" added to group "%s"',
			[
				'group' => $group->getGID(),
				'user' => $user->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	/**
	 * log remove user from group event
	 *
	 * @param IGroup $group
	 * @param IUser $user
	 */
	public function removeUser(IGroup $group, IUser $user): void {
		$this->log('User "%s" removed from group "%s"',
			[
				'group' => $group->getGID(),
				'user' => $user->getUID()
			],
			[
				'user', 'group'
			]
		);
	}

	/**
	 * log create group to group event
	 *
	 * @param IGroup $group
	 */
	public function createGroup(IGroup $group): void {
		$this->log('Group created: "%s"',
			[
				'group' => $group->getGID()
			],
			[
				'group'
			]
		);
	}

	/**
	 * log delete group to group event
	 *
	 * @param IGroup $group
	 */
	public function deleteGroup(IGroup $group): void {
		$this->log('Group deleted: "%s"',
			[
				'group' => $group->getGID()
			],
			[
				'group'
			]
		);
	}
}
