<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Migration\Attributes;

/**
 * enum ColumnType based on OCP\DB\Types
 *
 * @see \OCP\DB\Types
 * @since 30.0.0
 */
enum ColumnType : string {
	/** @since 30.0.0 */
	case BIGINT = 'bigint';
	/** @since 30.0.0 */
	case BINARY = 'binary';
	/** @since 30.0.0 */
	case BLOB = 'blob';
	/** @since 30.0.0 */
	case BOOLEAN = 'boolean';
	/** @since 30.0.0 */
	case DATE = 'date';
	/** @since 30.0.0 */
	case DATETIME = 'datetime';
	/** @since 30.0.0 */
	case DECIMAL = 'decimal';
	/** @since 30.0.0 */
	case FLOAT = 'float';
	/** @since 30.0.0 */
	case INTEGER = 'integer';
	/** @since 30.0.0 */
	case SMALLINT = 'smallint';
	/** @since 30.0.0 */
	case STRING = 'string';
	/** @since 30.0.0 */
	case TEXT = 'text';
	/** @since 30.0.0 */
	case TIME = 'time';
	/** @since 30.0.0 */
	case JSON = 'json';
}
