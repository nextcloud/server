<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Icewind\SMB;

/**
 * The `ISystem` interface provides a way to access system dependent information
 * such as the availability and location of certain binaries.
 */
interface ISystem {
	/**
	 * Get the path to a file descriptor of the current process
	 *
	 * @param int $num the file descriptor id
	 * @return string
	 */
	public function getFD(int $num): string;

	/**
	 * Get the full path to the `smbclient` binary of null if the binary is not available
	 *
	 * @return string|null
	 */
	public function getSmbclientPath(): ?string;

	/**
	 * Get the full path to the `net` binary of null if the binary is not available
	 *
	 * @return string|null
	 */
	public function getNetPath(): ?string;

	/**
	 * Get the full path to the `smbcacls` binary of null if the binary is not available
	 *
	 * @return string|null
	 */
	public function getSmbcAclsPath(): ?string;

	/**
	 * Get the full path to the `stdbuf` binary of null if the binary is not available
	 *
	 * @return string|null
	 */
	public function getStdBufPath(): ?string;

	/**
	 * Get the full path to the `date` binary of null if the binary is not available
	 *
	 * @return string|null
	 */
	public function getDatePath(): ?string;

	/**
	 * Whether or not the smbclient php extension is enabled
	 *
	 * @return bool
	 */
	public function libSmbclientAvailable(): bool;
}
