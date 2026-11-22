<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

/**
 * @since 30.0.0
 */
interface IPublicReferenceProvider extends IReferenceProvider {
	/**
	 * Return a reference with its metadata for a given reference identifier and sharingToken
	 *
	 * @since 30.0.0
	 */
	public function resolveReferencePublic(string $referenceText, string $sharingToken): ?IReference;

	/**
	 * Return a custom cache key to be used for caching the metadata
	 * This could be for example the current sharingToken if the reference
	 * access permissions are different for each share
	 *
	 * Should return null, if the cache is only related to the
	 * reference id and has no further dependency
	 *
	 * @since 30.0.0
	 */
	public function getCacheKeyPublic(string $referenceId, string $sharingToken): ?string;
}
