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
interface ICreateUserBackend {
	/**
	 * @since 14.0.0
	 *
	 * @param string $uid The username of the user to create
	 * @param string $password The password of the new user
	 * @return bool
	 */
	public function createUser(string $uid, string $password): bool;
}
