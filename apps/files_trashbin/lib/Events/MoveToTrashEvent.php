<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Trashbin\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Node;

/**
 * Class MoveToTrashEvent
 *
 * Event to allow other apps to disable the trash bin for specific files
 *
 * @package OCA\Files_Trashbin\Events
 * @since 28.0.0 Dispatched as a typed event
 */
class MoveToTrashEvent extends Event {

	/** @var bool */
	private $moveToTrashBin;

	/** @var Node */
	private $node;

	public function __construct(Node $node) {
		$this->moveToTrashBin = true;
		$this->node = $node;
	}

	/**
	 * get Node which will be deleted
	 *
	 * @return Node
	 */
	public function getNode() {
		return $this->node;
	}

	/**
	 * disable trash bin for this operation
	 */
	public function disableTrashBin() {
		$this->moveToTrashBin = false;
	}

	/**
	 * should the file be moved to the trash bin?
	 *
	 * @return bool
	 */
	public function shouldMoveToTrashBin() {
		return $this->moveToTrashBin;
	}
}
