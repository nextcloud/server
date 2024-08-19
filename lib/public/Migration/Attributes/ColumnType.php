<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

use OCP\DB\Types;

/**
 * enum ColumnType based on OCP\DB\Types
 *
 * @see \OCP\DB\Types
 * @since 30.0.0
 */
enum ColumnType : string {
	/** @since 30.0.0 */
	case BIGINT = Types::BIGINT;
	/** @since 30.0.0 */
	case BINARY = Types::BINARY;
	/** @since 30.0.0 */
	case BLOB = Types::BLOB;
	/** @since 30.0.0 */
	case BOOLEAN = Types::BOOLEAN;
	/**
	 * A column created with `DATE` can be used for both `DATE` and `DATE_IMMUTABLE`
	 * on the `\OCP\AppFramework\Db\Entity`.
	 * @since 30.0.0
	 */
	case DATE = Types::DATE;
	/**
	 * A column created with `DATETIME` can be used for both `DATETIME` and `DATETIME_IMMUTABLE`
	 * on the `\OCP\AppFramework\Db\Entity`.
	 * @since 30.0.0
	 */
	case DATETIME = Types::DATETIME;
	/**
	 * A column created with `DATETIME_TZ` can be used for both `DATETIME_TZ` and `DATETIME_TZ_IMMUTABLE`
	 * on the `\OCP\AppFramework\Db\Entity`.
	 * @since 31.0.0
	 */
	case DATETIME_TZ = Types::DATETIME_TZ;
	/** @since 30.0.0 */
	case DECIMAL = Types::DECIMAL;
	/** @since 30.0.0 */
	case FLOAT = Types::FLOAT;
	/** @since 30.0.0 */
	case INTEGER = Types::INTEGER;
	/** @since 30.0.0 */
	case SMALLINT = Types::SMALLINT;
	/** @since 30.0.0 */
	case STRING = Types::STRING;
	/** @since 30.0.0 */
	case TEXT = Types::TEXT;
	/** @since 30.0.0 */
	case TIME = Types::TIME;
	/** @since 30.0.0 */
	case JSON = Types::JSON;
}
