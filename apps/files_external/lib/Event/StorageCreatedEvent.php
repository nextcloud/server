<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Event;

use OCA\Files_External\Lib\StorageConfig;
use OCP\EventDispatcher\Event;

class StorageCreatedEvent extends Event {
	public function __construct(
		private readonly StorageConfig $newConfig,
	) {
		parent::__construct();
	}

	public function getNewConfig(): StorageConfig {
		return $this->newConfig;
	}
}
