<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * Event sent before a node is permanently deleted from the trash bin.
 *
 * @since 35.0.0
 */
class BeforeNodeDeletedEvent extends Event {
	public function __construct(
		private readonly Node $source,
	) {
		parent::__construct();
	}

	public function getSource(): Node {
		return $this->source;
	}
}
