<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1025Date20240308063933 extends SimpleMigrationStep {

	private IDBConnection $db;

	public function __construct(IDBConnection $db) {
		$this->db = $db;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		foreach (['addressbookchanges', 'calendarchanges'] as $tableName) {
			$table = $schema->getTable($tableName);
			if (!$table->hasColumn('created_at')) {
				$table->addColumn('created_at', Types::INTEGER, [
					'notnull' => true,
					'length' => 4,
					'default' => 0,
				]);
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options): void {
		foreach (['addressbookchanges', 'calendarchanges'] as $tableName) {
			$qb = $this->db->getQueryBuilder();

			$update = $qb->update($tableName)
				->set('created_at', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
				->where(
					$qb->expr()->eq('created_at', $qb->createNamedParameter(0, IQueryBuilder::PARAM_INT)),
				);

			$updated = $update->executeStatement();
			$output->debug('Added a default creation timestamp to ' . $updated . ' rows in ' . $tableName);
		}
	}

}
