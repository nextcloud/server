<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\DateTimeImmutableType;
use OCP\DB\Types;
use Override;

class TimestampImmutableType extends DateTimeImmutableType {
	#[Override]
	public function getName(): string {
		return Types::TIMESTAMP_IMMUTABLE;
	}

	#[Override]
	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
		$declaration = parent::getSQLDeclaration($column, $platform);

		if ($platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform) {
			$field = $column['notnull'] ?? true ? 'TIMESTAMP' : 'TIMESTAMP NULL';
			$declaration = str_replace('DATETIME', $field, $declaration);
		}
		return $declaration;
	}
}
