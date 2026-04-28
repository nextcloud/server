<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[AddColumn(table: 'addressbooks', name: 'isFederated', type: ColumnType::BOOLEAN)]
class Version1038Date20260331152450 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $db,
	) {
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$addressbooksTable = $schema->getTable('addressbooks');

		if (!$addressbooksTable->hasColumn('isFederated')) {
			$addressbooksTable->addColumn('isFederated', Types::BOOLEAN, [
				'default' => false,
			]);
		}

		return $schema;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$subQuery = $this->db->getQueryBuilder();
		$subQuery->select('url_hash')
			->from('trusted_servers');

		$qb = $this->db->getQueryBuilder();
		$qb->update('addressbooks')
			->set('isFederated', $qb->createNamedParameter(true, IQueryBuilder::PARAM_BOOL))
			->where($qb->expr()->in('uri', $qb->createFunction($subQuery->getSQL())))
			->executeStatement();
	}
}
