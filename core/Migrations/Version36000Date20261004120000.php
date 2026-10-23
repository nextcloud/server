<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[CreateTable(
	table: 'message_queue',
	columns: ['id', 'queue_name', 'message_class', 'body', 'retry_count', 'last_error', 'created_at', 'available_at', 'delivered_at', 'deduplication_hash'],
	description: 'New table to store the messages of the message queue',
)]
#[AddIndex(table: 'message_queue', type: IndexType::PRIMARY)]
#[AddIndex(table: 'message_queue', type: IndexType::INDEX, description: 'Allows to fetch the next available message of a queue')]
#[AddIndex(table: 'message_queue', type: IndexType::INDEX, description: 'Allows to find pending duplicates of a message')]
class Version36000Date20261004120000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('message_queue')) {
			$table = $schema->createTable('message_queue');
			$table->addColumn('id', Types::BIGINT, [
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('queue_name', Types::STRING, ['notnull' => true, 'length' => 32]);
			$table->addColumn('message_class', Types::STRING, ['notnull' => true, 'length' => 255]);
			$table->addColumn('body', Types::TEXT, ['notnull' => true]);
			$table->addColumn('retry_count', Types::INTEGER, ['notnull' => true, 'default' => 0, 'unsigned' => true]);
			$table->addColumn('last_error', Types::TEXT, ['notnull' => false]);
			$table->addColumn('created_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('available_at', Types::BIGINT, ['notnull' => true, 'unsigned' => true]);
			$table->addColumn('delivered_at', Types::BIGINT, ['notnull' => false, 'unsigned' => true]);
			$table->addColumn('deduplication_hash', Types::STRING, ['notnull' => false, 'length' => 64]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['queue_name', 'available_at'], 'mq_queue_available');
			$table->addIndex(['deduplication_hash'], 'mq_dedup_hash');
			// Makes sure there is no auto-increment in Oracle
			$schema->dropAutoincrementColumn('message_queue', 'id');

			return $schema;
		}

		return null;
	}
}
