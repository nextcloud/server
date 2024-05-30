<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

use OCP\IUser;

/**
 * Class UserManagement logs all user management related actions.
 *
 * @package OCA\AdminAudit\Actions
 */
class UserManagement extends Action {
	/**
	 * Log creation of users
	 *
	 * @param array $params
	 */
	public function create(array $params): void {
		$this->log(
			'User created: "%s"',
			$params,
			[
				'uid',
			]
		);
	}

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
	 * Log deletion of users
	 *
	 * @param array $params
	 */
	public function delete(array $params): void {
		$this->log(
			'User deleted: "%s"',
			$params,
			[
				'uid',
			]
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

	/**
	 * Log enabling of users
	 *
	 * @param array $params
	 */
	public function change(array $params): void {
		switch ($params['feature']) {
			case 'enabled':
				$this->log(
					$params['value'] === true
						? 'User enabled: "%s"'
						: 'User disabled: "%s"',
					['user' => $params['user']->getUID()],
					[
						'user',
					]
				);
				break;
			case 'eMailAddress':
				$this->log(
					'Email address changed for user %s',
					['user' => $params['user']->getUID()],
					[
						'user',
					]
				);
				break;
		}
	}

	/**
	 * Logs changing of the user scope
	 *
	 * @param IUser $user
	 */
	public function setPassword(IUser $user): void {
		if ($user->getBackendClassName() === 'Database') {
			$this->log(
				'Password of user "%s" has been changed',
				[
					'user' => $user->getUID(),
				],
				[
					'user',
				]
			);
		}
	}
}
