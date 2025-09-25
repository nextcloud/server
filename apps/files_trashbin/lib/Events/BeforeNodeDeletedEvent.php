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
 * Event send before a node is deleted definitively.
 * @since 32.0.0
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
