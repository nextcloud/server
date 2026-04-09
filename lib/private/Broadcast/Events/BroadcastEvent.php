<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Broadcast\Events;

use JsonSerializable;
use OCP\Broadcast\Events\IBroadcastEvent;
use OCP\EventDispatcher\ABroadcastedEvent;
use OCP\EventDispatcher\Event;

class BroadcastEvent extends Event implements IBroadcastEvent {
	public function __construct(
		private ABroadcastedEvent $event,
	) {
		parent::__construct();
	}

	public function getName(): string {
		return $this->event->broadcastAs();
	}

	public function getUids(): array {
		return $this->event->getUids();
	}

	public function getPayload(): JsonSerializable {
		return $this->event;
	}

	public function setBroadcasted(): void {
		$this->event->setBroadcasted();
	}
}
