<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
