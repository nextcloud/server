<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Authentication;

/**
 * Interface IProvideUserSecretBackend
 *
 * @since 23.0.0
 */
interface IProvideUserSecretBackend {
	/**
	 * Optionally returns a stable per-user secret. This secret is for
	 * instance used to secure file encryption keys.
	 * @return string
	 * @since 23.0.0
	 */
	public function getCurrentUserSecret(): string;
}
