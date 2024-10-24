<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Actions;

/**
 * Class Sharing logs the sharing actions
 *
 * @package OCA\AdminAudit\Actions
 */
class Sharing extends Action {

	/**
	 * Logs the updating of permission changes for shares
	 *
	 * @param array $params
	 */
	public function updatePermissions(array $params): void {
		$this->log(
			'The permissions of the shared %s "%s" with ID "%s" have been changed to "%s"',
			$params,
			[
				'itemType',
				'path',
				'itemSource',
				'permissions',
			]
		);
	}

	/**
	 * Logs the password changes for a share
	 *
	 * @param array $params
	 */
	public function updatePassword(array $params): void {
		$this->log(
			'The password of the publicly shared %s "%s" with ID "%s" has been changed',
			$params,
			[
				'itemType',
				'token',
				'itemSource',
			]
		);
	}

	/**
	 * Logs the expiration date changes for a share
	 *
	 * @param array $params
	 */
	public function updateExpirationDate(array $params): void {
		if ($params['date'] === null) {
			$this->log(
				'The expiration date of the publicly shared %s with ID "%s" has been removed',
				$params,
				[
					'itemType',
					'itemSource',
				]
			);
		} else {
			$this->log(
				'The expiration date of the publicly shared %s with ID "%s" has been changed to "%s"',
				$params,
				[
					'itemType',
					'itemSource',
					'date',
				]
			);
		}
	}

	/**
	 * Logs access of shared files
	 *
	 * @param array $params
	 */
	public function shareAccessed(array $params): void {
		$this->log(
			'The shared %s with the token "%s" by "%s" has been accessed.',
			$params,
			[
				'itemType',
				'token',
				'uidOwner',
			]
		);
	}
}
