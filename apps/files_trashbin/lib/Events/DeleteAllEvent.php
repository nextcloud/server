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
 * Event send before emptying the trash.
 * @since 32.0.0
 */
class DeleteAllEvent extends Event {

	/**
	 * @param Node[] $deletedNodes
	 */
	public function __construct(
		private readonly array $deletedNodes,
	) {
		parent::__construct();
	}

	/** @return Node[] */
	public function getDeletedNodes(): array {
		return $this->deletedNodes;
	}
}
