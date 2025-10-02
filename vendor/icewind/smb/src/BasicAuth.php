<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

class BasicAuth implements IAuth {
	/** @var string */
	private $username;
	/** @var string|null */
	private $workgroup;
	/** @var string */
	private $password;

	public function __construct(string $username, ?string $workgroup, string $password) {
		$this->username = $username;
		$this->workgroup = $workgroup;
		$this->password = $password;
	}

	public function getUsername(): ?string {
		return $this->username;
	}

	public function getWorkgroup(): ?string {
		return $this->workgroup;
	}

	public function getPassword(): ?string {
		return $this->password;
	}

	public function getExtraCommandLineArguments(): string {
		return ($this->workgroup) ? '-W ' . escapeshellarg($this->workgroup) : '';
	}

	public function setExtraSmbClientOptions($smbClientState): void {
		// noop
	}
}
