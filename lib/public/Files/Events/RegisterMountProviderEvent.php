<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\Events;

use OCP\EventDispatcher\Event;
use OCP\Files\Config\IMountProviderCollection;

/** @since 30.0.0 */
class RegisterMountProviderEvent extends Event {
	/**
	 * @since 30.0.0
	 */
	public function __construct(
		private IMountProviderCollection $mountProviderCollection,
	) {
	}

	/**
	 * Get the mount provider collection to register new providers
	 * @since 30.0.0
	 */
	public function getProviderCollection(): IMountProviderCollection {
		return $this->mountProviderCollection;
	}
}
