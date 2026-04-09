<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

use OCP\EventDispatcher\Event;
use Override;

/**
 * Class GenericEntityEvent
 *
 * @since 18.0.0
 */
class GenericEntityEvent implements IEntityEvent {
	/**
	 * @param non-empty-string $displayName
	 * @param class-string<Event> $eventName
	 * @since 18.0.0
	 */
	public function __construct(
		private readonly string $displayName,
		private readonly string $eventName,
	) {
	}

	#[Override]
	public function getDisplayName(): string {
		return $this->displayName;
	}

	#[Override]
	public function getEventName(): string {
		return $this->eventName;
	}
}
