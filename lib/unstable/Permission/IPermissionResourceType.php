<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Implementable;

/**
 * A kind of resource permissions can be applied to, e.g. files, forms or mails.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface IPermissionResourceType {
	/**
	 * Stable ID in the format `appid:name`, e.g. `mail:message`.
	 *
	 * The ID is persisted by consumers and must never change.
	 *
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getId(): string;

	/**
	 * @return non-empty-string
	 * @experimental 36.0.0
	 */
	public function getDisplayName(): string;

	/**
	 * The permissions supported by this resource type.
	 *
	 * @return list<PermissionBinding>
	 * @experimental 36.0.0
	 */
	public function getPermissionBindings(): array;
}
