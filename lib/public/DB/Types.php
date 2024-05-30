<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

/**
 * Database types supported by Nextcloud's DBs
 *
 * Use these constants instead of \Doctrine\DBAL\Types\Type or \Doctrine\DBAL\Types\Types
 *
 * @since 21.0.0
 */
final class Types {
	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const BIGINT = 'bigint';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const BINARY = 'binary';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const BLOB = 'blob';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const BOOLEAN = 'boolean';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const DATE = 'date';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const DATETIME = 'datetime';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const DECIMAL = 'decimal';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const FLOAT = 'float';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const INTEGER = 'integer';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const SMALLINT = 'smallint';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const STRING = 'string';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const TEXT = 'text';

	/**
	 * @var string
	 * @since 21.0.0
	 */
	public const TIME = 'time';

	/**
	 * @var string
	 * @since 24.0.0
	 */
	public const JSON = 'json';
}
