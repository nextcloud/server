<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

/**
 * @since 16.0.0
 *
 * Allow the backend to mark groups to be excluded from being shown in search dialogs
 */
interface IHideFromCollaborationBackend {
	/**
	 * Check if a group should be hidden from search dialogs
	 *
	 * @param string $groupId
	 * @return bool
	 * @since 16.0.0
	 */
	public function hideGroup(string $groupId): bool;
}
