<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairStartEvent extends Event {
	public function __construct(
		private int $max,
		private string $current,
	) {
	}

	public function getMaxStep(): int {
		return $this->max;
	}

	public function getCurrentStepName(): string {
		return $this->current;
	}
}
