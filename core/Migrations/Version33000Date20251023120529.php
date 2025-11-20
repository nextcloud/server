<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Use unique index for preview_locations
 */
#[AddIndex(table: 'preview_locations', type: IndexType::UNIQUE)]
class Version33000Date20251023120529 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('preview_locations')) {
			$table = $schema->getTable('preview_locations');
			$table->addUniqueIndex(['bucket_name', 'object_store_name'], 'unique_bucket_store');
		}

		return $schema;
	}

	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// This shouldn't run on a production instance, only daily
		$qb = $this->connection->getQueryBuilder();
		$qb->select('*')
			->from('preview_locations');
		$result = $qb->executeQuery();

		$set = [];

		while ($row = $result->fetchAssociative()) {
			// Iterate over all the rows with duplicated rows
			$id = $row['id'];

			if (isset($set[$row['bucket_name'] . '_' . $row['object_store_name']])) {
				// duplicate
				$authoritativeId = $set[$row['bucket_name'] . '_' . $row['object_store_name']];
				$qb = $this->connection->getQueryBuilder();
				$qb->select('id')
					->from('preview_locations')
					->where($qb->expr()->eq('bucket_name', $qb->createNamedParameter($row['bucket_name'])))
					->andWhere($qb->expr()->eq('object_store_name', $qb->createNamedParameter($row['object_store_name'])))
					->andWhere($qb->expr()->neq('id', $qb->createNamedParameter($authoritativeId)));

				$result = $qb->executeQuery();
				while ($row = $result->fetchAssociative()) {
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
				break;
			}
			$set[$row['bucket_name'] . '_' . $row['object_store_name']] = $row['id'];
		}
	}
}
