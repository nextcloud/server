<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\User\Backend;

/**
 * @since 20.0.0
 *
 * Allow backends to signal that they handle logout. For example
 * SSO providers that also have a SSO logout url
 */
interface ICustomLogout {
	/**
	 * @since 20.0.0
	 *
	 * The url to redirect to for logout
	 *
	 * @return string
	 */
	public function getLogoutUrl(): string;
}
