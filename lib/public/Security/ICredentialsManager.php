<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author J0WI <J0WI@users.noreply.github.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\Security;

/**
 * Store and retrieve credentials for external services
 *
 * @since 8.2.0
 */
interface ICredentialsManager {
	/**
	 * Store a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @param mixed $credentials
	 * @since 8.2.0
	 */
	public function store(string $userId, string $identifier, $credentials): void;

	/**
	 * Retrieve a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @return mixed
	 * @since 8.2.0
	 */
	public function retrieve(string $userId, string $identifier);

	/**
	 * Delete a set of credentials
	 *
	 * @param string $userId empty string for system-wide credentials
	 * @param string $identifier
	 * @return int rows removed
	 * @since 8.2.0
	 */
	public function delete(string $userId, string $identifier): int;

	/**
	 * Erase all credentials stored for a user
	 *
	 * @param string $userId
	 * @return int rows removed
	 * @since 8.2.0
	 */
	public function erase(string $userId): int;
}
