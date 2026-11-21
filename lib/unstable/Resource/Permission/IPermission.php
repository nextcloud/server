<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Something that can be allowed or restricted on a resource.
 *
 * A permission has one meaning wherever it is used. The app owning a resource enforces it,
 * other apps can restrict it without knowing what it does.
 *
 * Apps extend {@see APermission} and register the class with
 * {@see \OCP\AppFramework\Bootstrap\IRegistrationContext::registerResourcePermission}.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
interface IPermission {
	/**
	 * Stable ID in the format `appid:name`, the app ID has to be the ID of the registering app.
	 * The ID is persisted by other apps and must never change.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * Translated name of the permission.
	 *
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * Translated explanation of the permission.
	 *
	 * @experimental 36.0.0
	 */
	public function getHint(): ?string;

	/**
	 * The core permission this permission is a kind of.
	 * Whenever that core permission is restricted, this permission is restricted as well.
	 *
	 * Only the core permissions themselves return null.
	 *
	 * @experimental 36.0.0
	 */
	public function getKindOf(): ?CorePermission;
}
