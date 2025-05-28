<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * Event dispatched after a successful creation of a version
 */
class VersionCreatedEvent extends Event {
	public function __construct(
		private Node $node,
		private int $revision,
	) {
		parent::__construct();
	}

	/**
	 * Node of the file that has been versioned
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * Revision of the file that has been versioned
	 */
	public function getRevision(): int {
		return $this->revision;
	}
}
