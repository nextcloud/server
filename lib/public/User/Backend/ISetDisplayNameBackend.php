<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 14.0.0
 */
interface ISetDisplayNameBackend {
	/**
	 * @since 14.0.0
	 *
	 * @param string $uid The username
	 * @param string $displayName The new display name
	 * @return bool
	 *
	 * @since 25.0.0 Throw InvalidArgumentException
	 * @throws \InvalidArgumentException
	 */
	public function setDisplayName(string $uid, string $displayName): bool;
}
