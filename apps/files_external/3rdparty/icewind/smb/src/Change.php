<?php
/**
 * SPDX-FileCopyrightText: 2016 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: MIT
 */

namespace Icewind\SMB;

class Change {
	/** @var int */
	private $code;
	/** @var string */
	private $path;

	public function __construct(int $code, string $path) {
		$this->code = $code;
		$this->path = $path;
	}

	public function getCode(): int {
		return $this->code;
	}

	public function getPath(): string {
		return $this->path;
	}
}
