<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\SetupCheck;

/**
 * This interface needs to be implemented if you want to provide custom
 * setup checks in your application. The results of these checks will them
 * be displayed in the admin overview.
 *
 * @since 28.0.0
 */
interface ISetupCheck {
	/**
	 * @since 28.0.0
	 * @return string Category id, one of security/system/accounts, or a custom one which will be merged in system
	 */
	public function getCategory(): string;

	/**
	 * @since 28.0.0
	 * @return string Translated name to display to the user
	 */
	public function getName(): string;

	/**
	 * @since 28.0.0
	 */
	public function run(): SetupResult;
}
