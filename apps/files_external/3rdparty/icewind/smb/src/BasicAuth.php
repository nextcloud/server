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
