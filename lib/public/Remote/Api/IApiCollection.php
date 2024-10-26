<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote\Api;

/**
 * Provides access to the various apis of a remote instance
 *
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface IApiCollection {
	/**
	 * @return IUserApi
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getUserApi();

	/**
	 * @return ICapabilitiesApi
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getCapabilitiesApi();
}
