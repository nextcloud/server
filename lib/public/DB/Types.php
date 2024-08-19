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
	 * A datetime instance with only the date set.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DATE_IMMUTABLE` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 * @var string
	 * @since 21.0.0
	 */
	public const DATE = 'date';

	/**
	 * An immutable datetime instance with only the date set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `DATE` type because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 * @var string
	 * @since 31.0.0
	 */
	public const DATE_IMMUTABLE = 'date_immutable';

	/**
	 * A datetime instance with date and time support.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DATETIME_IMMUTABLE` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 * @var string
	 * @since 21.0.0
	 */
	public const DATETIME = 'datetime';

	/**
	 * An immutable datetime instance with date and time set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `DATETIME` type because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 * @var string
	 * @since 31.0.0
	 */
	public const DATETIME_IMMUTABLE = 'datetime_immutable';


	/**
	 * A datetime instance with timezone support
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DATETIME_TZ_IMMUTABLE` instead.
	 *
	 * @var string
	 * @since 31.0.0
	 */
	public const DATETIME_TZ = 'datetimetz';

	/**
	 * An immutable timezone aware datetime instance with date and time set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `DATETIME_TZ` type because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * @var string
	 * @since 31.0.0
	 */
	public const DATETIME_TZ_IMMUTABLE = 'datetimetz_immutable';

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
	 * A datetime instance with only the time set.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `TIME_IMMUTABLE` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 * @var string
	 * @since 21.0.0
	 */
	public const TIME = 'time';

	/**
	 * A datetime instance with only the time set.
	 * This will be (de)serialized into a \DateTime instance.
	 *
	 * It is recommended to use this over the `DATETIME_TZ` type because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * @var string
	 * @since 31.0.0
	 */
	public const TIME_IMMUTABLE = 'time_immutable';

	/**
	 * @var string
	 * @since 24.0.0
	 */
	public const JSON = 'json';
}
