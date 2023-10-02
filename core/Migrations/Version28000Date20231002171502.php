<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Migrations;

use Closure;
use OCA\Settings\Db\ClientDiagnosticMapper;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Introduce client_diagnostics table
 */
class Version28000Date20231002171502 extends SimpleMigrationStep {
	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(ClientDiagnosticMapper::TABLE_NAME)) {
			$table = $schema->createTable(ClientDiagnosticMapper::TABLE_NAME);

			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
				'autoincrement' => true,
			]);
			$table->addColumn('authtokenid', Types::BIGINT, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('diagnostic', Types::TEXT, [
				'notnull' => true,
			]);

			$table->setPrimaryKey(['id'], 'client_diagnostics_id_primary');
			$table->addUniqueIndex(['authtokenid'], 'client_diagnostics_authtokenid_index');

			$changed = true;
			return $schema;
		}

		return null;
	}
}
