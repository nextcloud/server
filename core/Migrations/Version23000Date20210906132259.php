<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version23000Date20210906132259 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/**
		 * Table was missing a primary key
		 * Therefore it was dropped with Version24000Date20211213081506
		 * and then recreated with a primary key in Version24000Date20211213081604
		 */
//		/** @var ISchemaWrapper $schema */
//		$schema = $schemaClosure();
//
//		$hasTable = $schema->hasTable(self::TABLE_NAME);
//
//		if (!$hasTable) {
//			$table = $schema->createTable(self::TABLE_NAME);
//			$table->addColumn('hash', Types::STRING, [
//				'notnull' => true,
//				'length' => 128,
//			]);
//			$table->addColumn('delete_after', Types::DATETIME, [
//				'notnull' => true,
//			]);
//			$table->addIndex(['hash'], 'ratelimit_hash');
//			$table->addIndex(['delete_after'], 'ratelimit_delete_after');
//			return $schema;
//		}

		return null;
	}
}
