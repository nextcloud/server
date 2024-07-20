<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\AppFramework\Bootstrap;

/**
 * @psalm-immutable
 * @template-extends ServiceRegistration<\OCP\EventDispatcher\IEventListener>
 */
class EventListenerRegistration extends ServiceRegistration {
	/** @var string */
	private $event;

	/** @var int */
	private $priority;

	public function __construct(string $appId,
		string $event,
		string $service,
		int $priority) {
		parent::__construct($appId, $service);
		$this->event = $event;
		$this->priority = $priority;
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
