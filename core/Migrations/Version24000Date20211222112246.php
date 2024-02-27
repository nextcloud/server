<?php

declare(strict_types=1);

/**
 * @copyright 2021 Vitor Mattos <vitor@php.rio>
 *
 * @author Vitor Mattos <vitor@php.rio>
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

class Version24000Date20211222112246 extends SimpleMigrationStep {
	private const TABLE_NAME = 'reactions';

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$action = false;
		$comments = $schema->getTable('comments');
		if (!$comments->hasColumn('reactions')) {
			$comments->addColumn('reactions', Types::STRING, [
				'notnull' => false,
				'length' => 4000,
			]);
			$action = true;
		}

		if (!$schema->hasTable(self::TABLE_NAME)) {
			$table = $schema->createTable(self::TABLE_NAME);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('parent_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('message_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('actor_type', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('actor_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('reaction', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['reaction'], 'comment_reaction');
			$table->addIndex(['parent_id'], 'comment_reaction_parent_id');
			$table->addUniqueIndex(['parent_id', 'actor_type', 'actor_id', 'reaction'], 'comment_reaction_unique');
			$action = true;
		}
		return $action ? $schema : null;
	}
}
