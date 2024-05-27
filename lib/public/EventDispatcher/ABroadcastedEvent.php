<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\EventDispatcher;

use JsonSerializable;

/**
 * @since 18.0.0
 */
abstract class ABroadcastedEvent extends Event implements JsonSerializable {
	/**
	 * @since 18.0.0
	 */
	private $broadcasted = false;

	/**
	 * Get the name of the event, as received on the client-side
	 *
	 * Uses the fully qualified event class name by default
	 *
	 * @return string
	 * @since 18.0.0
	 */
	public function broadcastAs(): string {
		return get_class($this);
	}

	/**
	 * @return string[]
	 * @since 18.0.0
	 */
	abstract public function getUids(): array;

	/**
	 * @since 18.0.0
	 */
	public function setBroadcasted(): void {
		$this->broadcasted = true;
	}

	/**
	 * @since 18.0.0
	 */
	public function isBroadcasted(): bool {
		return $this->broadcasted;
	}
}
