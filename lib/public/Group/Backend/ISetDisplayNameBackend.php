<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Group\Backend;

/**
 * @since 18.0.0
 */
interface ISetDisplayNameBackend {
	/**
	 * @param string $gid
	 * @param string $displayName
	 * @return bool
	 * @since 18.0.0
	 */
	public function setDisplayName(string $gid, string $displayName): bool;
}
