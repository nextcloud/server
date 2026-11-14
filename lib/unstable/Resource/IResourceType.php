<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource;

use OCP\AppFramework\Attribute\Implementable;

/**
 * A kind of resource an app owns, e.g. a table or a mail.
 *
 * Register implementations with {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerResourceType}.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IResourceType {
	/**
	 * Stable ID in the format `appid:type`, the app ID has to be the ID of the registering app.
	 * The ID is persisted by other apps and must never change.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * Translated name of the resource type.
	 *
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * IDs of the permissions that apply to resources of this type.
	 *
	 * @return list<non-empty-string>
	 * @experimental 36.0.0
	 */
	public function getPermissionIds(): array;
}
