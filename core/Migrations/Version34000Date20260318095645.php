<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[ModifyColumn(table: 'jobs', name: 'argument', type: ColumnType::TEXT, description: 'Migrate background job arguments to a text column')]
class Version34000Date20260318095645 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('jobs')) {
			$table = $schema->getTable('jobs');
			$argumentColumn = $table->getColumn('argument');

			if ($argumentColumn->getType() !== Type::getType(Types::TEXT)) {
				$argumentColumn->setType(Type::getType(Types::TEXT));
				return $schema;
			}
		}

		return null;
	}
}
