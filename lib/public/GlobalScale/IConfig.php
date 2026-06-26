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
}
