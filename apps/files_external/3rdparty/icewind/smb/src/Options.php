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

class Options implements IOptions {
	/** @var int */
	private $timeout = 20;

	/** @var string|null */
	private $minProtocol;
	/** @var string|null */
	private $maxProtocol;

	public function getTimeout(): int {
		return $this->timeout;
	}

	public function setTimeout(int $timeout): void {
		$this->timeout = $timeout;
	}

	public function getMinProtocol(): ?string {
		return $this->minProtocol;
	}

	public function setMinProtocol(?string $minProtocol): void {
		$this->minProtocol = $minProtocol;
	}

	public function getMaxProtocol(): ?string {
		return $this->maxProtocol;
	}

	public function setMaxProtocol(?string $maxProtocol): void {
		$this->maxProtocol = $maxProtocol;
	}
}
