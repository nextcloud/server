<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrate away from auto-increment
 */
#[AddIndex(table: 'preview_locations', type: IndexType::UNIQUE)]
#[ModifyColumn(table: 'preview_locations', name: 'id', description: 'Remove auto-increment')]
#[ModifyColumn(table: 'previews', name: 'id', description: 'Remove auto-increment')]
#[ModifyColumn(table: 'preview_versions', name: 'id', description: 'Remove auto-increment')]
class Version33000Date20251023110529 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {}

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('preview_locations')) {
			$table = $schema->getTable('preview_locations');
			$table->modifyColumn('id', ['autoincrement' => false]);
			$table->addUniqueIndex(['bucket_name', 'object_store_name'], 'unique_bucket_store');
		}

		if ($schema->hasTable('preview_versions')) {
			$table = $schema->getTable('preview_versions');
			$table->modifyColumn('id', ['autoincrement' => false]);
		}

		if ($schema->hasTable('previews')) {
			$table = $schema->getTable('previews');
			$table->modifyColumn('id', ['autoincrement' => false]);
		}

		return $schema;
	}

	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// This code should never be run on a production instance but this might be needed on daily/git version.
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('preview_locations')
			->groupBy('bucket_name', 'object_store_name')
			->having('COUNT(*) > 1');

		$result = $qb->executeQuery();
		while ($row = $result->fetch()) {
			// Iterate over all the rows with duplicated rows
			$id = $row['id'];

			$qb = $this->connection->getQueryBuilder();
			$qb->select('id')
				->from('preview_locations')
				->where($qb->expr()->eq('bucket_name', $qb->createNamedParameter($row['bucket_name'])))
				->andWhere($qb->expr()->eq('object_store_name', $qb->createNamedParameter($row['object_store_name'])))
				->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($row['id'])));

			$result = $qb->executeQuery();
			while ($row = $result->fetch()) {
				// Update previews entries to the now de-duplicated id
				$qb = $this->connection->getQueryBuilder();
				$qb->update('previews')
					->set('location_id', $qb->createNamedParameter($id))
					->where($qb->expr()->eq('id', $qb->createNamedParameter($row['id'])));
				$qb->executeStatement();

				$qb = $this->connection->getQueryBuilder();
				$qb->delete('preview_locations')
					->where($qb->expr()->eq('id', $qb->createNamedParameter($row['id'])));
				$qb->executeStatement();
			}
		}
	}
}
