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

#[CreateTable(table: 'preview_failures', columns: ['id', 'file_id', 'user_id', 'path', 'mime', 'provider', 'error', 'attempts', 'last_attempt', 'created_at'], description: 'Failed preview generation attempts for the Previews admin page')]
#[AddIndex(table: 'preview_failures', type: IndexType::PRIMARY)]
#[AddIndex(table: 'preview_failures', type: IndexType::UNIQUE, description: 'One failure row per file')]
#[AddIndex(table: 'preview_failures', type: IndexType::INDEX, description: 'Look up failures by last attempt')]
class Version35000Date20260823120000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('preview_failures')) {
			return null;
		}

		$table = $schema->createTable('preview_failures');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('file_id', Types::BIGINT, [
			'notnull' => true,
			'length' => 20,
		]);
		$table->addColumn('user_id', Types::STRING, [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('path', Types::TEXT, [
			'notnull' => false,
		]);
		$table->addColumn('mime', Types::STRING, [
			'notnull' => true,
			'length' => 255,
			'default' => '',
		]);
		$table->addColumn('provider', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('error', Types::TEXT, [
			'notnull' => true,
		]);
		$table->addColumn('attempts', Types::INTEGER, [
			'notnull' => true,
			'default' => 1,
			'unsigned' => true,
		]);
		$table->addColumn('last_attempt', Types::INTEGER, [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('created_at', Types::INTEGER, [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['file_id'], 'preview_failures_file_id');
		$table->addIndex(['last_attempt'], 'preview_failures_last_attempt');
		$table->addIndex(['mime'], 'preview_failures_mime');

		return $schema;
	}
}
