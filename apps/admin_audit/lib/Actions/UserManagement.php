<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

/**
 * Class UserManagement logs all user management related actions.
 *
 * @package OCA\AdminAudit\Actions
 */
class UserManagement extends Action {

	/**
	 * Log assignments of users (typically user backends)
	 *
	 * @param string $uid
	 */
	public function assign(string $uid): void {
		$this->log(
			'UserID assigned: "%s"',
			[ 'uid' => $uid ],
			[ 'uid' ]
		);
	}

	/**
	 * Log unassignments of users (typically user backends, no data removed)
	 *
	 * @param string $uid
	 */
	public function unassign(string $uid): void {
		$this->log(
			'UserID unassigned: "%s"',
			[ 'uid' => $uid ],
			[ 'uid' ]
		);
	}
}
