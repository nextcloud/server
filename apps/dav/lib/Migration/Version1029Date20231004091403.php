<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1029Date20231004091403 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('dav_absence')) {
			$table = $schema->createTable('dav_absence');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('first_day', Types::STRING, [
				'length' => 10,
				'notnull' => true,
			]);
			$table->addColumn('last_day', Types::STRING, [
				'length' => 10,
				'notnull' => true,
			]);
			$table->addColumn('status', Types::STRING, [
				'length' => 100,
				'notnull' => true,
			]);
			$table->addColumn('message', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addUniqueIndex(['user_id'], 'dav_absence_uid_idx');
		} else {
			$table = $schema->getTable('dav_absence');
		}

		if ($table->getPrimaryKey() === null) {
			$table->setPrimaryKey(['id'], 'dav_absence_id_idx');
		}

		return $schema;
	}
}
