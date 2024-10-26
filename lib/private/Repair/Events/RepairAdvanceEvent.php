<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairAdvanceEvent extends Event {
	private int $increment;
	private string $description;

	public function __construct(
		int $increment,
		string $description,
	) {
		$this->increment = $increment;
		$this->description = $description;
	}

	public function getIncrement(): int {
		return $this->increment;
	}

	public function getDescription(): string {
		return $this->description;
	}
}
