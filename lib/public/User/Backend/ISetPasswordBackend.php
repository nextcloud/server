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
interface ISetPasswordBackend {
	/**
	 * @since 14.0.0
	 *
	 * @param string $uid The username
	 * @param string $password The new password
	 * @return bool
	 */
	public function setPassword(string $uid, string $password): bool;
}
