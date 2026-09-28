<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Repair\Events;

use OCP\EventDispatcher\Event;

class RepairErrorEvent extends Event {
	private string $message;

	public function __construct(
		string $message,
	) {
		$this->message = $message;
	}

	public function getMessage(): string {
		return $this->message;
	}
}
