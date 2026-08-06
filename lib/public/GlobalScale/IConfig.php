<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\GlobalScale;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Interface IConfig
 *
 * Configuration of the global scale architecture
 *
 * @since 12.0.1
 */
#[Consumable(since: '12.0.1')]
interface IConfig {
	/**
	 * Check if global scale is enabled.
	 *
	 * @since 12.0.1
	 */
	public function isGlobalScaleEnabled(): bool;

	/**
	 * Check if federation should only be used internally in a global scale setup.
	 *
	 * @since 12.0.1
	 */
	public function onlyInternalFederation(): bool;

	/**
	 * Check if the current instance is the primary instance.
	 *
	 * This instance is then only used to log in the users and then redirect them
	 * to their associated secondary instance.
	 *
	 * @since 34.0.3
	 */
	public function isPrimary(): bool;

	/**
	 * Check if the current instance is one of the secondary instance.
	 *
	 * These instances are the actual instance of a user and hold all their data.
	 *
	 * @since 34.0.3
	 */
	public function isSecondary(): bool;

	/**
	 * Check if the given user is one of the admin on the primary instance.
	 *
	 * These users won't be redirected to a secondary instance and instead will
	 * stay on the primary instance to manage the configuration of the instance.
	 *
	 * @since 34.0.3
	 */
	public function isPrimaryAdmin(string $userId): bool;
}
