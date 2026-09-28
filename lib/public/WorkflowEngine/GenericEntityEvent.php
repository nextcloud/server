<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\WorkflowEngine;

/**
 * Class GenericEntityEvent
 *
 *
 * @since 18.0.0
 */
class GenericEntityEvent implements IEntityEvent {
	/** @var string */
	private $displayName;
	/** @var string */
	private $eventName;

	/**
	 * GenericEntityEvent constructor.
	 *
	 * @since 18.0.0
	 */
	public function __construct(string $displayName, string $eventName) {
		if (trim($displayName) === '') {
			throw new \InvalidArgumentException('DisplayName must not be empty');
		}
		if (trim($eventName) === '') {
			throw new \InvalidArgumentException('EventName must not be empty');
		}

		$this->displayName = trim($displayName);
		$this->eventName = trim($eventName);
	}

	/**
	 * returns a translated name to be presented in the web interface.
	 *
	 * Example: "created" (en), "kreita" (eo)
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string {
		return $this->displayName;
	}

	/**
	 * returns the event name that is emitted by the EventDispatcher, e.g.:
	 *
	 * Example: "OCA\MyApp\Factory\Cats::postCreated"
	 *
	 * @since 18.0.0
	 */
	public function getEventName(): string {
		return $this->eventName;
	}
}
