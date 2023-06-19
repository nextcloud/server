<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023 Robin Appelman <robin@icewind.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
