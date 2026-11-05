<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Permission;

use OCP\AppFramework\Attribute\Consumable;

/**
 * The kind of values a permission accepts.
 *
 * New cases may be added in the future. Consumers must ignore permissions with a value type they do not know.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum PermissionValueType: string {
	/**
	 * `'false'` (denied) or `'true'` (allowed), see {@see ABooleanPermission}.
	 *
	 * @experimental 36.0.0
	 */
	case Boolean = 'boolean';

	/**
	 * One of a fixed, ordered list of options, see {@see AEnumPermission}.
	 *
	 * @experimental 36.0.0
	 */
	case Enum = 'enum';
}
