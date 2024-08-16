<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
