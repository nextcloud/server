<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Schema;

use Doctrine\DBAL\Schema\ForeignKeyConstraint as DBALForeignKeyConstraint;
use OCP\DB\Schema\IForeignKeyConstraint;
use Override;

class ForeignKeyConstraint implements IForeignKeyConstraint {

	public function __construct(
		private readonly DBALForeignKeyConstraint $keyConstraint,
	) {
	}

	#[Override]
	public function getName(): string {
		/** @var non-empty-string $value */
		$value = $this->keyConstraint->getName();
		return $value;
	}
}
