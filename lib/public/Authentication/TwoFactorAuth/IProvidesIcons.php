<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Authentication\TwoFactorAuth;

/**
 * Interface for two-factor providers that provide dark and light provider
 * icons
 *
 * @since 15.0.0
 */
interface IProvidesIcons extends IProvider {
	/**
	 * Get the path to the light (white) icon of this provider
	 *
	 * @return String
	 *
	 * @since 15.0.0
	 */
	public function getLightIcon(): String;

	/**
	 * Get the path to the dark (black) icon of this provider
	 *
	 * @return String
	 *
	 * @since 15.0.0
	 */
	public function getDarkIcon(): String;
}
