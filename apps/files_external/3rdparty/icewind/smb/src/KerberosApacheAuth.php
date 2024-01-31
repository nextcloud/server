<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
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

namespace Icewind\SMB;

use Icewind\SMB\Exception\DependencyException;
use Icewind\SMB\Exception\Exception;
use Icewind\SMB\Exception\InvalidTicket;

/**
 * Use existing kerberos ticket to authenticate and reuse the apache ticket cache (mod_auth_kerb)
 *
 * @deprecated Use `KerberosAuth` with `$auth->setTicket(KerberosTicket::fromEnv())` instead
 */
class KerberosApacheAuth extends KerberosAuth implements IAuth {
	public function getTicket(): KerberosTicket {
		if ($this->ticket === null) {
			$ticket = KerberosTicket::fromEnv();
			if ($ticket === null) {
				throw new InvalidTicket("No ticket found in environment");
			}
			$this->ticket = $ticket;
		}
		return $this->ticket;
	}

	/**
	 * Copy the ticket to a temporary location and use that ticket for authentication
	 *
	 * @return void
	 */
	public function copyTicket(): void {
		$this->ticket = KerberosTicket::load($this->getTicket()->save());
	}

	/**
	 * Check if a valid kerberos ticket is present
	 *
	 * @return bool
	 */
	public function checkTicket(): bool {
		return $this->getTicket()->isValid();
	}
}
