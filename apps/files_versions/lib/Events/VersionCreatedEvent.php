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
 * Class VersionCreatedEvent
 *
 * Event called after a successful creation of a version
 *
 * @package OCA\Files_Versions
 */
class VersionCreatedEvent extends Event {
	/**
	 * VersionCreatedEvent constructor.
	 *
	 * @param Node $node
	 */
	public function __construct(
		private Node $node,
		private int $revision,
	) {
	}

	/**
	 * get Node of the file that has been versioned
	 *
	 * @return Node
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
