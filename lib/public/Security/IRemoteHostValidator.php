<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Security;

/**
 * Validator for remote hosts
 *
 * @since 26.0.0
 */
interface IRemoteHostValidator {
	/**
	 * Validate if a host may be connected to
	 *
	 * By default, Nextcloud does not connect to any local servers. That is neither
	 * localhost nor any host in the local network.
	 *
	 * Admins can overwrite this behavior with the global `allow_local_remote_servers`
	 * settings flag. If the flag is set to `true`, local hosts will be considered
	 * valid.
	 *
	 * @param string $host hostname of the remote server, IPv4 or IPv6 address
	 *
	 * @return bool
	 * @since 26.0.0
	 */
	public function isValid(string $host): bool;
}
