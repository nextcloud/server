<?php
/**
 * SPDX-FileCopyrightText: 2018 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
