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
interface ICheckPasswordBackend {
	/**
	 * @since 14.0.0
	 *
	 * @param string $loginName The loginname
	 * @param string $password The password
	 * @return string|false The uid on success false on failure
	 */
	public function checkPassword(string $loginName, string $password);
}
