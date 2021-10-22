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

use Icewind\SMB\Exception\Exception;

/**
 * Use existing kerberos ticket to authenticate
 */
class KerberosAuth implements IAuth {
	public function getUsername(): ?string {
		return 'dummy';
	}

	public function getWorkgroup(): ?string {
		return 'dummy';
	}

	public function getPassword(): ?string {
		return null;
	}

	public function getExtraCommandLineArguments(): string {
		return '-k';
	}

	public function setExtraSmbClientOptions($smbClientState): void {
		$success = (bool)smbclient_option_set($smbClientState, SMBCLIENT_OPT_USE_KERBEROS, true);
		$success = $success && smbclient_option_set($smbClientState, SMBCLIENT_OPT_FALLBACK_AFTER_KERBEROS, false);

		if (!$success) {
			throw new Exception("Failed to set smbclient options for kerberos auth");
		}
	}
}
