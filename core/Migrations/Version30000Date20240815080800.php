<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 S1m <git@sgougeon.fr>
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'webauthn', name: 'user_verification', type: ColumnType::BOOLEAN)]
class Version30000Date20240815080800 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('webauthn');
		$table->addColumn('user_verification', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
		return $schema;
	}
}
