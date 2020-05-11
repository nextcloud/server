<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Ben Klang <bklang@powerhrg.com>
 *
 * @author Ben Klang <bklang@powerhrg.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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

class Version20000Date20200511105314 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('collres_accesscache') && !$schema->getTable('collres_accesscache')->hasPrimaryKey()) {
			// Converts a unique key to a primary key for compatibility with Percona XtraDB
			// See https://github.com/nextcloud/server/issues/16311
			// Step 2: Re-create the index as a Primary Key
			$schema->getTable('collres_accesscache')->setPrimaryKey(['user_id', 'collection_id', 'resource_type', 'resource_id'], 'collres_unique_user');
		}

		if ($schema->hasTable('collres_resources') && !$schema->getTable('collres_resources')->hasPrimaryKey()) {
			// Converts a unique key to a primary key for compatibility with Percona XtraDB
			// See https://github.com/nextcloud/server/issues/16311
			// Step 2: Re-create the index as a Primary Key
			$schema->getTable('collres_resources')->setPrimaryKey(['collection_id', 'resource_type', 'resource_id'], 'collres_unique_res');
		}

		return $schema;
	}
}
