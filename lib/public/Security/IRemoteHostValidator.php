<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
