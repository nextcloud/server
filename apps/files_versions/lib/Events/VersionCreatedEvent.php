<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Events;

use OCA\Files_Versions\Versions\IVersion;
use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * Event dispatched after a successful creation of a version
 */
class VersionCreatedEvent extends Event {
	public function __construct(
		private Node $node,
		private IVersion $version,
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
	 * Version of the file that was created
	 */
	public function getVersion(): IVersion {
		return $this->version;
	}
}
