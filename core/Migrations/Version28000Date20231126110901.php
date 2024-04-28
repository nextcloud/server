<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new fields for type and lazy loading in appconfig for the new IAppConfig API.
 */
class Version28000Date20231126110901 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		return null;

		/**
		 * this migration was needed during Nextcloud 28 to prep the migration to 29 and a
		 * new IAppConfig as its API require 'lazy' and 'type' database field.
		 *
		 * some changes in the migration process and the expected result have made its execution
		 * useless, therefore ignored.
		 *
		 * @see Version29000Date20240124132201
		 * @see Version29000Date20240124132202
		 */
		//		/** @var ISchemaWrapper $schema */
		//		$schema = $schemaClosure();
		//
		//		if (!$schema->hasTable('appconfig')) {
		//			return null;
		//		}
		//
		//		$table = $schema->getTable('appconfig');
		//		if ($table->hasColumn('lazy')) {
		//			return null;
		//		}
		//
		//		// type=2 means value is typed as MIXED
		//		$table->addColumn('type', Types::INTEGER, ['notnull' => true, 'default' => 2]);
		//		$table->addColumn('lazy', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
		//
		//		return $schema;
	}
}
