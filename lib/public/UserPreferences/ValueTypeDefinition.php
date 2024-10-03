<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\UserPreferences;

/**
 * Listing of value type definition for user preferences
 *
 * @see IUserPreferences
 * @since 31.0.0
 */
enum ValueTypeDefinition: string {
	/** @since 30.0.0 */
	case MIXED = 'mixed';
	/** @since 30.0.0 */
	case STRING = 'string';
	/** @since 30.0.0 */
	case INT = 'int';
	/** @since 30.0.0 */
	case FLOAT = 'float';
	/** @since 30.0.0 */
	case BOOL = 'bool';
	/** @since 30.0.0 */
	case ARRAY = 'array';
}
