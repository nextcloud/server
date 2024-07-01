<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\DB;

use Doctrine\DBAL\Types\Exception\TypeNotRegistered;
use Doctrine\DBAL\Types\Type;
use OC\DB\Exceptions\DbalException;

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

	/**
	 * @param Type $type
	 * @return string
	 * @throws Exception
	 * @since 30.0.0
	 */
	public static function getType(Type $type): string {
		try {
			$doctrineType = $type->getTypeRegistry()->lookupName($type);
		} catch (\Doctrine\DBAL\Exception $e) {
			throw DbalException::wrap($e);
		}

		return match ($doctrineType) {
			self::BIGINT,
			self::BINARY,
			self::BLOB,
			self::BOOLEAN,
			self::DATE,
			self::DATETIME,
			self::DECIMAL,
			self::FLOAT,
			self::INTEGER,
			self::SMALLINT,
			self::STRING,
			self::TEXT,
			self::TIME,
			self::JSON => $doctrineType,
			default => throw DbalException::wrap(new TypeNotRegistered(sprintf('Type of the class %s is not registered.', $doctrineType))),
		};
	}
}
