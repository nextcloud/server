<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Authentication;

/**
 * Interface IApacheBackend
 *
 * @since 6.0.0
 */
interface IApacheBackend {
	/**
	 * In case the user has been authenticated by a module true is returned.
	 *
	 * @return boolean whether the module reports a user as currently logged in.
	 * @since 6.0.0
	 */
	public function isSessionActive();

	/**
	 * Gets the current logout URL
	 *
	 * @return string
	 * @since 12.0.3
	 */
	public function getLogoutUrl();

	/**
	 * Return the id of the current user
	 * @return string
	 * @since 6.0.0
	 */
	public function getCurrentUserId();
}
