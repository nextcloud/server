<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021 Robin Appelman <robin@icewind.nl>
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

class StringBuffer {
	/** @var string */
	private $buffer = "";
	/** @var int */
	private $pos = 0;

	public function clear(): void {
		$this->buffer = "";
		$this->pos = 0;
	}

	public function push(string $data): int {
		$this->buffer = $this->flush() . $data;
		return strlen($data);
	}

	public function remaining(): int {
		return strlen($this->buffer) - $this->pos;
	}

	public function read(int $count): string {
		$chunk = substr($this->buffer, $this->pos, $count);
		$this->pos += strlen($chunk);
		return $chunk;
	}

	public function flush(): string {
		if ($this->pos === 0) {
			$remaining = $this->buffer;
		} else {
			$remaining = substr($this->buffer, $this->pos);
		}

		$this->clear();

		return $remaining;
	}
}
