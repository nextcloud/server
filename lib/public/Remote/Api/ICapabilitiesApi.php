<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Remote\Api;

/**
 * @since 13.0.0
 * @deprecated 23.0.0
 */
interface ICapabilitiesApi {
	/**
	 * @return array The capabilities in the form of [$appId => [$capability => $value]]
	 *
	 * @since 13.0.0
	 * @deprecated 23.0.0
	 */
	public function getCapabilities();
}
