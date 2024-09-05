<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrate the argument_hash column of oc_jobs to use sha256 instead of md5.
 */
class Version28000Date20240828142927 extends SimpleMigrationStep {
	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// Increase the column size from 32 to 64
		$table = $schema->getTable('jobs');
		$table->modifyColumn('argument_hash', [
			'notnull' => false,
			'length' => 64,
		]);

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$chunkSize = 1000;
		$offset = 0;
		$nullHash = hash('sha256', 'null');

		$selectQuery = $this->connection->getQueryBuilder()
			->select('*')
			->from('jobs')
			->setMaxResults($chunkSize);

		$insertQuery = $this->connection->getQueryBuilder();
		$insertQuery->update('jobs')
			->set('argument_hash', $insertQuery->createParameter('argument_hash'))
			->where($insertQuery->expr()->eq('id', $insertQuery->createParameter('id')));

		do {
			$result = $selectQuery
				->setFirstResult($offset)
				->executeQuery();

			$jobs = $result->fetchAll();
			$count = count($jobs);

			foreach ($jobs as $jobRow) {
				if ($jobRow['argument'] === 'null') {
					$hash = $nullHash;
				} else {
					$hash = hash('sha256', $jobRow['argument']);
				}
				$insertQuery->setParameter('id', (string)$jobRow['id'], IQueryBuilder::PARAM_INT);
				$insertQuery->setParameter('argument_hash', $hash);
				$insertQuery->executeStatement();
			}

			$offset += $chunkSize;
		} while ($count === $chunkSize);
	}
}
