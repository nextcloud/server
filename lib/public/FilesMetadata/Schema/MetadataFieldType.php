<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Schema;

use OCP\AppFramework\Attribute\Consumable;

/**
 * Value type of a metadata field, defining how its value is validated and displayed.
 *
 * @since 36.0.0
 */
#[Consumable(since: '36.0.0')]
enum MetadataFieldType: string {
	/**
	 * Free text value
	 *
	 * @since 36.0.0
	 */
	case STRING = 'string';

	/**
	 * Integer value
	 *
	 * @since 36.0.0
	 */
	case INT = 'int';

	/**
	 * Decimal value
	 *
	 * @since 36.0.0
	 */
	case FLOAT = 'float';

	/**
	 * Boolean value, displayed as a checkbox
	 *
	 * @since 36.0.0
	 */
	case BOOL = 'bool';

	/**
	 * Point in time, stored as a unix timestamp
	 *
	 * @since 36.0.0
	 */
	case DATE = 'date';

	/**
	 * One value out of a fixed list of options, e.g. displayed as a dropdown
	 *
	 * @see MetadataField::getOptions()
	 * @since 36.0.0
	 */
	case ENUM = 'enum';
}
