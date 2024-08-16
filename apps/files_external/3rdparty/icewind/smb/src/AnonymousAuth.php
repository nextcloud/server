<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

use Icewind\SMB\Exception\Exception;

class AnonymousAuth implements IAuth {
	public function getUsername(): ?string {
		return null;
	}

	public function getWorkgroup(): ?string {
		return 'dummy';
	}

	public function getPassword(): ?string {
		return null;
	}

	public function getExtraCommandLineArguments(): string {
		return '-N';
	}

	public function setExtraSmbClientOptions($smbClientState): void {
		if (smbclient_option_set($smbClientState, SMBCLIENT_OPT_AUTO_ANONYMOUS_LOGIN, true) === false) {
			throw new Exception("Failed to set smbclient options for anonymous auth");
		}
	}
}
