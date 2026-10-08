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
use Doctrine\DBAL\Types\SmallIntType;
use OCP\DB\Types;
use Override;

class TinyIntType extends SmallIntType {
	#[Override]
	public function getName(): string {
		return Types::TINYINT;
	}

	#[Override]
	public function getSQLDeclaration(array $column, AbstractPlatform $platform): string {
		$declaration = parent::getSQLDeclaration($column, $platform);
		return $platform instanceof MySQLPlatform || $platform instanceof MariaDBPlatform
			? str_replace('SMALLINT', 'TINYINT', $declaration)
			: $declaration;
	}
}
