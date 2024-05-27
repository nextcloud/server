<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Log;

use OCP\EventDispatcher\Event;

/**
 * Even for when a log item is being logged
 *
 * @since 28.0.0
 */
class BeforeMessageLoggedEvent extends Event {
	private int $level;
	private string $app;
	private $message;

	/**
	 * @param string $app
	 * @param int $level
	 * @param array $message
	 * @since 28.0.0
	 */
	public function __construct(string $app, int $level, array $message) {
		$this->level = $level;
		$this->app = $app;
		$this->message = $message;
	}

	/**
	 * Get the level of the log item
	 *
	 * @return int
	 * @since 28.0.0
	 */
	public function getLevel(): int {
		return $this->level;
	}


	/**
	 * Get the app context of the log item
	 *
	 * @return string
	 * @since 28.0.0
	 */
	public function getApp(): string {
		return $this->app;
	}


	/**
	 * Get the message of the log item
	 *
	 * @return array
	 * @since 28.0.0
	 */
	public function getMessage(): array {
		return $this->message;
	}
}
