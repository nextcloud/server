<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Enum;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Expected type for each argument contained in the ocm path
 *
 * @since 33.0.0
 */
#[Consumable(since: '33.0.0')]
enum ParamType: string {
	case STRING = 'string';
	case INT = 'int';
	case FLOAT = 'float';
	case BOOL = 'bool';
}
