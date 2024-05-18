<?php
/**
 * @copyright Copyright (c) 2016, Roger Szabo (roger.szabo@web.de)
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\User_LDAP;

use OCP\IServerContainer;
use OCP\LDAP\ILDAPProvider;
use OCP\LDAP\ILDAPProviderFactory;

class LDAPProviderFactory implements ILDAPProviderFactory {
	/** * @var IServerContainer */
	private $serverContainer;

	public function __construct(IServerContainer $serverContainer) {
		$this->serverContainer = $serverContainer;
	}

	public function getLDAPProvider(): ILDAPProvider {
		return $this->serverContainer->get(LDAPProvider::class);
	}

	public function isAvailable(): bool {
		return true;
	}
}
