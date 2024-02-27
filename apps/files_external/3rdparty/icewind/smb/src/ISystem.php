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
