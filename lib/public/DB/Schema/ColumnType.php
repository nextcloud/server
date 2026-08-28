<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\DB\Schema;

/**
 * Database column types supported by Nextcloud's DBs.
 *
 * @since 35.0.0
 */
enum ColumnType: string {
	/**
	 * @since 35.0.0
	 */
	case Bigint = 'bigint';

	/**
	 * @since 35.0.0
	 */
	case Binary = 'binary';

	/**
	 * @since 35.0.0
	 */
	case Blob = 'blob';

	/**
	 * @since 35.0.0
	 */
	case Boolean = 'boolean';

	/**
	 * A datetime instance with only the date set.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DateImmutable` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 *
	 * @since 35.0.0
	 */
	case Date = 'date';

	/**
	 * An immutable datetime instance with only the date set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `Date` case because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 *
	 * @since 35.0.0
	 */
	case DateImmutable = 'date_immutable';

	/**
	 * A datetime instance with date and time support.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DatetimeImmutable` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 *
	 * @since 35.0.0
	 */
	case Datetime = 'datetime';

	/**
	 * An immutable datetime instance with date and time set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `Datetime` case because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 *
	 * @since 35.0.0
	 */
	case DatetimeImmutable = 'datetime_immutable';

	/**
	 * A datetime instance with timezone support
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `DatetimeTzImmutable` instead.
	 *
	 * @since 35.0.0
	 */
	case DatetimeTz = 'datetimetz';

	/**
	 * An immutable timezone aware datetime instance with date and time set.
	 * This will be (de)serialized into a \DateTimeImmutable instance,
	 * It is recommended to use this over the `DatetimeTz` case because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * @since 35.0.0
	 */
	case DatetimeTzImmutable = 'datetimetz_immutable';

	/**
	 * @since 35.0.0
	 */
	case Decimal = 'decimal';

	/**
	 * @since 35.0.0
	 */
	case Float = 'float';

	/**
	 * @since 35.0.0
	 */
	case Integer = 'integer';

	/**
	 * @since 35.0.0
	 */
	case Smallint = 'smallint';

	/**
	 * @since 35.0.0
	 */
	case String = 'string';

	/**
	 * @since 35.0.0
	 */
	case Text = 'text';

	/**
	 * A datetime instance with only the time set.
	 * This will be (de)serialized into a \DateTime instance,
	 * it is recommended to instead use the `TimeImmutable` instead.
	 *
	 * Warning: When deserialized the timezone will be set to UTC.
	 *
	 * @since 35.0.0
	 */
	case Time = 'time';

	/**
	 * A datetime instance with only the time set.
	 * This will be (de)serialized into a \DateTime instance.
	 *
	 * It is recommended to use this over the `DatetimeTz` case because
	 * out `Entity` class works detecting changes through the setter,
	 * changes on mutable objects can not be detected.
	 *
	 * @since 35.0.0
	 */
	case TimeImmutable = 'time_immutable';

	/**
	 * JSON fields can not properly be used in WHERE statements of Oracle and MySQL.
	 * It is recommended to use a simple {@see self::String} field and handle JSON within PHP.
	 *
	 * @since 35.0.0
	 */
	case Json = 'json';

	/**
	 * @since 35.0.0
	 */
	public function getName(): string {
		return $this->value;
	}
}
