<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
