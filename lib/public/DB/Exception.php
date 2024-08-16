<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use Exception as BaseException;

/**
 * Database exception
 *
 * Thrown by Nextcloud's database abstraction layer. This is the base class that
 * any specific exception will extend. Use this class in your try-catch to catch
 * *any* error related to the database. Use any of the subclasses in the same
 * namespace if you are only interested in specific errors.
 *
 * @psalm-immutable
 * @since 21.0.0
 */
class Exception extends BaseException {
	/**
	 * Nextcloud lost connection to the database
	 *
	 * @since 21.0.0
	 */
	public const REASON_CONNECTION_LOST = 1;

	/**
	 * A database constraint was violated
	 *
	 * @since 21.0.0
	 */
	public const REASON_CONSTRAINT_VIOLATION = 2;

	/**
	 * A database object (table, column, index) already exists
	 *
	 * @since 21.0.0
	 */
	public const REASON_DATABASE_OBJECT_EXISTS = 3;

	/**
	 * A database object (table, column, index) can't be found
	 *
	 * @since 21.0.0
	 */
	public const REASON_DATABASE_OBJECT_NOT_FOUND = 4;

	/**
	 * The database ran into a deadlock
	 *
	 * @since 21.0.0
	 */
	public const REASON_DEADLOCK = 5;

	/**
	 * The database driver encountered an issue
	 *
	 * @since 21.0.0
	 */
	public const REASON_DRIVER = 6;

	/**
	 * A foreign key constraint was violated
	 *
	 * @since 21.0.0
	 */
	public const REASON_FOREIGN_KEY_VIOLATION = 7;

	/**
	 * An invalid argument was passed to the database abstraction
	 *
	 * @since 21.0.0
	 */
	public const REASON_INVALID_ARGUMENT = 8;

	/**
	 * A field name was invalid
	 *
	 * @since 21.0.0
	 */
	public const REASON_INVALID_FIELD_NAME = 9;

	/**
	 * A name in the query was ambiguous
	 *
	 * @since 21.0.0
	 */
	public const REASON_NON_UNIQUE_FIELD_NAME = 10;

	/**
	 * A not null constraint was violated
	 *
	 * @since 21.0.0
	 */
	public const REASON_NOT_NULL_CONSTRAINT_VIOLATION = 11;

	/**
	 * A generic server error was encountered
	 *
	 * @since 21.0.0
	 */
	public const REASON_SERVER = 12;

	/**
	 * A syntax error was reported by the server
	 *
	 * @since 21.0.0
	 */
	public const REASON_SYNTAX_ERROR = 13;

	/**
	 * A unique constraint was violated
	 *
	 * @since 21.0.0
	 */
	public const REASON_UNIQUE_CONSTRAINT_VIOLATION = 14;

	/**
	 * The lock wait timeout was exceeded
	 *
	 * @since 30.0.0
	 */
	public const REASON_LOCK_WAIT_TIMEOUT = 15;

	/**
	 * @return int|null
	 * @psalm-return Exception::REASON_*
	 * @since 21.0.0
	 */
	public function getReason(): ?int {
		return null;
	}
}
