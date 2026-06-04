<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairAdvanceEvent extends Event {
	public function __construct(
		private int $increment,
		private string $description,
	) {
	}

	public function getIncrement(): int {
		return $this->increment;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
