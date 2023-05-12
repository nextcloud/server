<?php

declare(strict_types=1);

/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Louis Chemineau <louis@chmn.me>
 * @author timm2k <timm2k@gmx.de>
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
 * Add oc_file_metadata table
 * @see \OC\Metadata\FileMetadata
 */
class Version24000Date20220404230027 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('file_metadata')) {
			$table = $schema->createTable('file_metadata');
			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('group_name', Types::STRING, [
				'notnull' => true,
				'length' => 50,
			]);
			$table->addColumn('value', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
			$table->setPrimaryKey(['id', 'group_name'], 'file_metadata_idx');

			return $schema;
		}

		return null;
	}
}
