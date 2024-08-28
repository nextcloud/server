<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
