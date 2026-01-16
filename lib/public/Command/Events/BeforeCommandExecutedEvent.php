<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Command\Events;

use OCP\EventDispatcher\Event;

/**
 * Dispatched before an occ command is executed
 *
 * @since 32.0.0
 */
class BeforeCommandExecutedEvent extends Event {
	/**
	 * @since 32.0.0
	 * @internal instances are created by Nextcloud server
	 */
	public function __construct(
		private string $command,
	) {
		parent::__construct();
	}

	/**
	 * @since 32.0.0
	 */
	public function getCommand(): string {
		return $this->command;
	}

}
