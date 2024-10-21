<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * Class CreateVersionEvent
 *
 * Event to allow other apps to disable versions for specific files
 *
 * @package OCA\Files_Versions
 */
class CreateVersionEvent extends Event {


	/** @var bool */
	private $createVersion;

	/**
	 * CreateVersionEvent constructor.
	 *
	 * @param Node $node
	 */
	public function __construct(
		private Node $node,
	) {
		$this->createVersion = true;
	}

	/**
	 * get Node of the file which should be versioned
	 *
	 * @return Node
	 */
	public function getNode(): Node {
		return $this->node;
	}

	/**
	 * disable versions for this file
	 */
	public function disableVersions(): void {
		$this->createVersion = false;
	}

	/**
	 * should a version be created for this file?
	 *
	 * @return bool
	 */
	public function shouldCreateVersion(): bool {
		return $this->createVersion;
	}
}
