<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

use OCP\EventDispatcher\IEventListener;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<IEventListener>
 */
class EventListenerRegistration extends ServiceRegistration {
	public function __construct(
		string $appId,
		private string $event,
		string $service,
		private int $priority,
	) {
		parent::__construct($appId, $service);
	}

	/**
	 * @return string
	 */
	public function getEvent(): string {
		return $this->event;
	}

	/**
	 * @return int
	 */
	public function getPriority(): int {
		return $this->priority;
	}
}
