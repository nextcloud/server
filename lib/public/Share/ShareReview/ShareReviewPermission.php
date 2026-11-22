<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Share\ShareReview;

use OCP\AppFramework\Attribute\Consumable;

/**
 * A single permission granted by an app-managed share, as exposed to a
 * share-review app through {@see ShareReviewEntry::$permissions}.
 *
 * Permissions are identified by an opaque, namespaced string owned by the
 * emitting app and carry their own localized display metadata, so a
 * share-review app can render any app-specific permission without
 * interpreting it.
 *
 * @since 34.0.2
 */
#[Consumable(since: '34.0.2')]
final class ShareReviewPermission {
	/**
	 * Identifiers for the permissions of files/folder shares, owned by the
	 * files app. Other apps MUST NOT emit these — every app uses its own
	 * namespace, even for permissions with the same name (e.g. "deck:edit").
	 *
	 * @since 34.0.2
	 */
	public const FILES_READ = 'files:read';

	/**
	 * @since 34.0.2
	 */
	public const FILES_UPDATE = 'files:update';

	/**
	 * @since 34.0.2
	 */
	public const FILES_CREATE = 'files:create';

	/**
	 * @since 34.0.2
	 */
	public const FILES_DELETE = 'files:delete';

	/**
	 * @since 34.0.2
	 */
	public const FILES_RESHARE = 'files:reshare';

	/**
	 * @param string $id Opaque, stable identifier owned by the emitting app,
	 *                   prefixed with the emitting app's ID
	 *                   ("<appId>:<permission>", e.g. "deck:manage"). Apps
	 *                   never share identifiers, even for permissions with
	 *                   the same name. Consumers must not parse or interpret
	 *                   the identifier beyond equality checks (e.g. for icon
	 *                   or translation lookup).
	 * @param string $displayName Localized, human readable label.
	 * @param string|null $hint Optional localized description.
	 * @param int $priority 1-100, higher is listed first.
	 *
	 * @since 34.0.2
	 */
	public function __construct(
		public readonly string $id,
		public readonly string $displayName,
		public readonly ?string $hint = null,
		public readonly int $priority = 50,
	) {
	}
}
