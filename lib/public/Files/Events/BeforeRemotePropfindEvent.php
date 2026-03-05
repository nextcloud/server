<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;

/**
 * This event is fired before a PROPFIND request is sent to remote WebDAV storage
 * Used to extend the list of properties to request additional data from the remote server
 *
 * @since 33.0.0
 */
class BeforeRemotePropfindEvent extends Event {
	public function __construct(
		private array $properties,
	) {
		parent::__construct();
	}

	/**
	 * @return array<string>
	 * @since 33.0.0
	 */
	public function getProperties(): array {
		return $this->properties;
	}

	/**
	 * @param array<string> $properties
	 * @since 33.0.0
	 */
	public function addProperties(array $properties): void {
		array_push($this->properties, ...$properties);
	}
}
