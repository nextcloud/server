<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Interface to be implemented by apps that want to expose their app-managed
 * shares to a share-review app. Implementations are registered through
 * {@see RegisterShareReviewSourceEvent} and resolved from the dependency
 * injection container.
 *
 * @since 34.0.2
 */
#[Implementable(since: '34.0.2')]
interface IShareReviewSource {
	/**
	 * The name of the app, used in the review table
	 *
	 * @since 34.0.2
	 */
	public function getName(): string;

	/**
	 * Return all app-specific shares.
	 *
	 * The app name is added by the share-review app from getName().
	 *
	 * @return list<ShareReviewEntry>
	 *
	 * @since 34.0.2
	 */
	public function getShares(): array;

	/**
	 * Delete an app-specific share.
	 *
	 * @since 34.0.2
	 */
	public function deleteShare(string $shareId): bool;
}
