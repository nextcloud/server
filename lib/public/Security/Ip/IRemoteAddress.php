<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Security\Ip;

/**
 * IP address of the connected client
 *
 * @since 30.0.0
 */
interface IRemoteAddress {
	/**
	 * Check if the current remote address is allowed to perform admin actions
	 * @since 30.0.0
	 */
	public function allowsAdminActions(): bool;
}
