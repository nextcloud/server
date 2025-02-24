<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Versions\Events;

use OCA\Files_Versions\Versions\IVersion;
use OCP\EventDispatcher\Event;

/**
 * Class VersionRestoredEvent
 *
 * Event that is called after a successful restore of a previous version
 *
 * @package OCA\Files_Versions
 */
class VersionRestoredEvent extends Event {
	public function __construct(
		private IVersion $version,
	) {
	}

	/**
	 * Version that was restored
	 */
	public function getVersion(): IVersion {
		return $this->version;
	}
}
