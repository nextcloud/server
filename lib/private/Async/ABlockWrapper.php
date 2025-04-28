<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Async;

use OC\Async\Model\Block;
use OC\Async\Model\SessionInterface;
use OCP\Async\Enum\BlockActivity;

abstract class ABlockWrapper {
	abstract public function session(array $metadata): void;
	abstract public function init(): void;
	abstract public function activity(BlockActivity $activity, string $line = ''): void;
	abstract public function end(string $line = ''): void;

	protected Block $block;
	private SessionInterface $sessionInterface;

	public function setBlock(Block $block): void {
		$this->block = $block;
	}

	public function getSessionInterface(): SessionInterface {
		return $this->sessionInterface;
	}

	public function setSessionInterface(SessionInterface $iface): void {
		$this->sessionInterface = $iface;
	}

	public function getReplayCount(): int {
		return $this->block->getReplayCount();
	}
}
