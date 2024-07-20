<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Console;

use OCP\EventDispatcher\Event;

/**
 * Class ConsoleEvent
 *
 * @since 9.0.0
 */
class ConsoleEvent extends Event {
	/**
	 * @since 9.0.0
	 * @deprecated 22.0.0
	 */
	public const EVENT_RUN = 'OC\Console\Application::run';

	/** @var string */
	protected $event;

	/** @var string[] */
	protected $arguments;

	/**
	 * DispatcherEvent constructor.
	 *
	 * @param string $event
	 * @param string[] $arguments
	 * @since 9.0.0
	 */
	public function __construct($event, array $arguments) {
		$this->event = $event;
		$this->arguments = $arguments;
	}

	/**
	 * @return string
	 * @since 9.0.0
	 */
	public function getEvent() {
		return $this->event;
	}

	/**
	 * @return string[]
	 * @since 9.0.0
	 */
	public function getArguments() {
		return $this->arguments;
	}
}
