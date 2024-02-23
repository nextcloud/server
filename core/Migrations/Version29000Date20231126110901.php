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
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Create new fields for type and lazy loading in appconfig for the new IAppConfig API.
 */
class Version29000Date20231126110901 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('appconfig');
		if ($table->hasColumn('lazy')) {
			return null;
		}

		/**
		 * This code is now useless, after a discussion about boolean on oracle;
		 * it has been decided to use another type for the lazy field
		 *
		 * a better migration process is available there:
		 *
		 * @see Version29000Date20240124132201 for the revert of current migration
		 * @see Version29000Date20240124132202 for the new migration process
		 */
		return null;

		//		// type=2 means value is typed as MIXED
		//		$table->addColumn('type', Types::INTEGER, ['notnull' => true, 'default' => 2]);
		//		$table->addColumn('lazy', Types::BOOLEAN, ['notnull' => false, 'default' => false]);
		//
		//		if ($table->hasIndex('appconfig_config_key_index')) {
		//			$table->dropIndex('appconfig_config_key_index');
		//		}
		//
		//		$table->addIndex(['lazy'], 'ac_lazy_i');
		//		$table->addIndex(['appid', 'lazy'], 'ac_app_lazy_i');
		//		$table->addIndex(['appid', 'lazy', 'configkey'], 'ac_app_lazy_key_i');
		//
		//		return $schema;
	}
}
