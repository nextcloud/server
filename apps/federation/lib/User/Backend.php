<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

declare(strict_types=1);

namespace OCA\Federation\User;

use OCP\Federation\ICloudIdManager;
use OCP\User\Backend\ABackend;
use OCP\User\Backend\IGetDisplayNameBackend;

class Backend extends ABackend implements IGetDisplayNameBackend {
	public function __construct(
		private ICloudIdManager $cloudIdManager,
	) {
	}

	#[\Override]
	public function getBackendName() {
		return 'federation';
	}

	#[\Override]
	public function deleteUser($uid) {
		return false;
	}

	#[\Override]
	public function getUsers($search = '', $limit = null, $offset = null) {
		return [];
	}

	/**
	 * Check if a user exists. Assume that the user with valid cloud id exists since the account is for a remote user
	 */
	#[\Override]
	public function userExists($uid) {
		return $this->cloudIdManager->isValidCloudId($uid);
	}

	#[\Override]
	public function getDisplayName($uid): string {
		return $this->cloudIdManager->resolveCloudId($uid)->getDisplayId();
	}

	#[\Override]
	public function getDisplayNames($search = '', $limit = null, $offset = null) {
		return [];
	}

	#[\Override]
	public function hasUserListings() {
		return false;
	}
}
