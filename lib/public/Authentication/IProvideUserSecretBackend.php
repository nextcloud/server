<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal Nextcloud classes

namespace OCP\Authentication;

use OCP\HintException;

/**
 * Interface IProvideUserSecretBackend
 *
 * @since 23.0.0
 */
interface IProvideUserSecretBackend {
	/**
	 * Optionally returns a stable per-user secret. This secret is for
	 * instance used to secure file encryption keys.
	 *
	 * @return non-empty-string|null Returns the per-user secret if the backend
	 *                               is configured or null otherwise.
	 * @throws HintException when the backend is configured to return a per-user
	 *                       secret but is unable to do so.
	 *
	 * @since 23.0.0
	 * @since 35.0.0 The returns value is now optional.
	 */
	public function getCurrentUserSecret(): ?string;
}
