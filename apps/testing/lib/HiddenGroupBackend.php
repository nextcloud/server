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
	public function __construct(
		private string $groupName = 'hidden_group',
	) {
	}

	#[\Override]
	public function inGroup($uid, $gid): bool {
		return false;
	}

	#[\Override]
	public function getUserGroups($uid): array {
		return [];
	}

	#[\Override]
	public function getGroups($search = '', $limit = -1, $offset = 0): array {
		return $offset === 0 ? [$this->groupName] : [];
	}

	#[\Override]
	public function groupExists($gid): bool {
		return $gid === $this->groupName;
	}

	#[\Override]
	public function usersInGroup($gid, $search = '', $limit = -1, $offset = 0): array {
		return [];
	}

	#[\Override]
	public function hideGroup(string $groupId): bool {
		return true;
	}
}
