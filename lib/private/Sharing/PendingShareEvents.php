<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use OCP\EventDispatcher\Event;

/**
 * Events of share changes that are dispatched once the surrounding transaction was committed
 */
final class PendingShareEvents {
	/** @var list<Event> */
	private array $events = [];

	public function add(Event $event): void {
		$this->events[] = $event;
	}

	/**
	 * @return list<Event>
	 */
	public function pull(): array {
		$events = $this->events;
		$this->events = [];
		return $events;
	}
}
