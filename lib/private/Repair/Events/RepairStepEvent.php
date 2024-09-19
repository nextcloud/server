<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairStepEvent extends Event {
	private string $stepName;

	public function __construct(
		string $stepName,
	) {
		$this->stepName = $stepName;
	}

	public function getStepName(): string {
		return $this->stepName;
	}
}
