<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

/**
 * @since 30.0.0
 */
interface ICreateNamedGroupBackend {
	/**
	 * Tries to create a group from its name.
	 *
	 * If group name already exists, null is returned.
	 * Otherwise, new group ID is returned.
	 *
	 * @param string $name Group name
	 * @return ?string Group ID in case of success, null in case of failure
	 * @since 30.0.0
	 */
	public function createGroup(string $name): ?string;
}
