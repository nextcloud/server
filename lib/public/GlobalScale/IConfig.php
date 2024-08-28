<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\GlobalScale;

/**
 * Interface IConfig
 *
 * Configuration of the global scale architecture
 *
 * @since 12.0.1
 */
interface IConfig {
	/**
	 * check if global scale is enabled
	 *
	 * @since 12.0.1
	 * @return bool
	 */
	public function isGlobalScaleEnabled();

	/**
	 * check if federation should only be used internally in a global scale setup
	 *
	 * @since 12.0.1
	 * @return bool
	 */
	public function onlyInternalFederation();
}
