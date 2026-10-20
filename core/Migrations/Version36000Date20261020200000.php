<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
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

/**
 * Remember the address a login flow v2 was started from
 */
#[AddColumn(table: 'login_flow_v2', name: 'initiator_ip', type: ColumnType::STRING)]
class Version36000Date20261020200000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('login_flow_v2')) {
			$table = $schema->getTable('login_flow_v2');
			if (!$table->hasColumn('initiator_ip')) {
				$table->addColumn('initiator_ip', Types::STRING, [
					'notnull' => false,
					'length' => 45,
				]);
				return $schema;
			}
		}

		return null;
	}
}
