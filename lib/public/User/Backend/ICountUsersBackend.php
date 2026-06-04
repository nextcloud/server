<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 14.0.0
 * @deprecated 31.0.0 use and implement ILimitAwareCountUsersBackend instead.
 */
interface ICountUsersBackend {
	/**
	 * @since 14.0.0
	 *
	 * @return int|false The number of users on success false on failure
	 */
	public function countUsers();
}
