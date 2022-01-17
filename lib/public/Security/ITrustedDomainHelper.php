<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
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
 *
 */

namespace OCP\Security;

/**
 * Allows checking domains and full URLs against the list of trusted domains for
 * this server in the config file.
 *
 * @package OCP\Security
 * @since 23.0.0
 */
interface ITrustedDomainHelper {
	/**
	 * Checks whether a given URL is considered as trusted from the list
	 * of trusted domains in the server's config file. If no trusted domains
	 * have been configured and the url is valid, returns true.
	 *
	 * @param string $url
	 * @return bool
	 * @since 23.0.0
	 */
	public function isTrustedUrl(string $url): bool;

	/**
	 * Checks whether a given domain is considered as trusted from the list
	 * of trusted domains in the server's config file. If no trusted domains
	 * have been configured, returns true.
	 * This is used to prevent Host Header Poisoning.
	 *
	 * @param string $domainWithPort
	 * @return bool
	 * @since 23.0.0
	 */
	public function isTrustedDomain(string $domainWithPort): bool;
}
