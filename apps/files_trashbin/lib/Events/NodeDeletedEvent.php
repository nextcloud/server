<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Trashbin\Events;

use OCP\EventDispatcher\Event;

/**
 * Event sent after a node was permanently deleted from the trash bin.
 *
 * @since 35.0.0
 */
class NodeDeletedEvent extends Event {
	public function __construct(
		private readonly string $sourcePath,
	) {
		parent::__construct();
	}

	public function getSourcePath(): string {
		return $this->sourcePath;
	}
}
