<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

interface IAuth {
	public function getUsername(): ?string;

	public function getWorkgroup(): ?string;

	public function getPassword(): ?string;

	/**
	 * Any extra command line option for smbclient that are required
	 *
	 * @return string
	 */
	public function getExtraCommandLineArguments(): string;

	/**
	 * Set any extra options for libsmbclient that are required
	 *
	 * @param resource $smbClientState
	 */
	public function setExtraSmbClientOptions($smbClientState): void;
}
