<?php
/**
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author root <root@localhost.localdomain>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCP\LDAP;

use OCP\IServerContainer;

/**
 * Interface ILDAPProviderFactory
 *
 * This class is responsible for instantiating and returning an ILDAPProvider
 * instance.
 *
 * @since 11.0.0
 */
interface ILDAPProviderFactory {
	/**
	 * Constructor for the LDAP provider factory
	 *
	 * @param IServerContainer $serverContainer server container
	 * @since 11.0.0
	 */
	public function __construct(IServerContainer $serverContainer);

	/**
	 * creates and returns an instance of the ILDAPProvider
	 *
	 * @return ILDAPProvider
	 * @since 11.0.0
	 */
	public function getLDAPProvider();

	/**
	 * Check if an ldap provider is available
	 *
	 * @return bool
	 * @since 21.0.0
	 */
	public function isAvailable(): bool;
}
