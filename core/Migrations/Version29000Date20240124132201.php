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
 * Create new column for type and remove previous lazy column in appconfig (will be recreated by Version29000Date20240124132202) for the new IAppConfig API.
 */
class Version29000Date20240124132201 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('appconfig');

		// we will drop 'lazy', we start to clean related indexes first
		if ($table->hasIndex('ac_lazy_i')) {
			$table->dropIndex('ac_lazy_i');
		}

		if ($table->hasIndex('ac_app_lazy_i')) {
			$table->dropIndex('ac_app_lazy_i');
		}

		if ($table->hasIndex('ac_app_lazy_key_i')) {
			$table->dropIndex('ac_app_lazy_key_i');
		}

		if ($table->hasColumn('lazy')) {
			$table->dropColumn('lazy');
		}

		// create field 'type' if it does not exist yet, or fix the fact that it is missing 'unsigned'
		if (!$table->hasColumn('type')) {
			$table->addColumn('type', Types::INTEGER, ['notnull' => true, 'default' => 2, 'unsigned' => true]);
		} else {
			$table->modifyColumn('type', ['notnull' => true, 'default' => 2, 'unsigned' => true]);
		}

		// not needed anymore
		if ($table->hasIndex('appconfig_config_key_index')) {
			$table->dropIndex('appconfig_config_key_index');
		}

		return $schema;
	}
}
