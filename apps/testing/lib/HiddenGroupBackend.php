<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Testing;

use OCP\Group\Backend\ABackend;
use OCP\Group\Backend\IHideFromCollaborationBackend;

class HiddenGroupBackend extends ABackend implements IHideFromCollaborationBackend {
	private string $groupName;

	public function __construct(
		string $groupName = 'hidden_group',
	) {
		$this->groupName = $groupName;
	}

	public function inGroup($uid, $gid): bool {
		return false;
	}

	public function getUserGroups($uid): array {
		return [];
	}

	public function getGroups($search = '', $limit = -1, $offset = 0): array {
		return $offset === 0 ? [$this->groupName] : [];
	}

	public function groupExists($gid): bool {
		return $gid === $this->groupName;
	}

	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		return [];
	}

	public function hideGroup(string $groupId): bool {
		return true;
	}
}
