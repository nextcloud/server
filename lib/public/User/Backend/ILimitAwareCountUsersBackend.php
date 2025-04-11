<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\User\Backend;

/**
 * @since 31.0.0
 */
interface ILimitAwareCountUsersBackend extends ICountUsersBackend {
	/**
	 * @since 31.0.0
	 *
	 * @param int $limit Limit to stop counting users if there are more than $limit. 0 to disable limiting.
	 * @return int|false The number of users (may be limited to $limit) on success false on failure
	 */
	public function countUsers(int $limit = 0): int|false;
}
