<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Resource\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The permissions every other permission is a kind of.
 * The cases are these root permissions; `DOWNLOAD` is a core permission as well but a kind of `Read`, so it is not a case.
 *
 * They are independent of each other, e.g. a file drop allows to create without allowing to read.
 * The app owning a resource applies its own dependencies when enforcing them.
 *
 * New cases may be added in future versions.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum CorePermission: string {
	/**
	 * ID of the download permission, which is a kind of {@see self::Read}.
	 *
	 * @experimental 36.0.0
	 */
	public const string DOWNLOAD = 'core:download';

	/**
	 * @experimental 36.0.0
	 */
	case Read = 'core:read';
	/**
	 * @experimental 36.0.0
	 */
	case Update = 'core:update';
	/**
	 * @experimental 36.0.0
	 */
	case Create = 'core:create';
	/**
	 * @experimental 36.0.0
	 */
	case Delete = 'core:delete';
	/**
	 * @experimental 36.0.0
	 */
	case Share = 'core:share';
}
