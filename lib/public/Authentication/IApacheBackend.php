<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\Authentication;

/**
 * Contract for user backends that integrate authentication performed outside
 * of Nextcloud.
 *
 * Typical implementations integrate with web-server authentication modules,
 * reverse proxies, or other external authentication services. Implementations
 * include the user_saml and user_oidc apps.
 *
 * The interface name is historical and is retained for backwards
 * compatibility; it is not limited to Apache.
 *
 * @since 6.0.0
 */
interface IApacheBackend {
	/**
	 * Whether the backend's external authentication mechanism considers the
	 * current user logged in.
	 *
	 * @since 6.0.0
	 */
	public function isSessionActive(): bool;

	/**
	 * Returns the URL to use when logging out of this backend's external
	 * authentication flow.
	 *
	 * @return non-empty-string
	 * @since 12.0.3
	 */
	public function getLogoutUrl(): string;

	/**
	 * Returns the ID of the Nextcloud user corresponding to the externally
	 * authenticated user.
	 *
	 * @since 6.0.0
	 */
	public function getCurrentUserId(): string;
}
