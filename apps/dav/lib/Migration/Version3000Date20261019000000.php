<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[AddColumn(table: 'calendarsubscriptions', name: 'credentials', type: ColumnType::TEXT, description: 'Encrypted credentials extracted from the source url')]
class Version3000Date20261019000000 extends SimpleMigrationStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('calendarsubscriptions');
		if ($table->hasColumn('credentials')) {
			return null;
		}

		$table->addColumn('credentials', Types::TEXT, [
			'notnull' => false,
		]);

		return $schema;
	}

	#[Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$select = $this->connection->getQueryBuilder();
		$select->select('id', 'source')
			->from('calendarsubscriptions')
			->where($select->expr()->like('source', $select->createNamedParameter('%@%')))
			->andWhere($select->expr()->isNull('credentials'));
		$result = $select->executeQuery();

		$update = $this->connection->getQueryBuilder();
		$update->update('calendarsubscriptions')
			->set('source', $update->createParameter('source'))
			->set('credentials', $update->createParameter('credentials'))
			->where($update->expr()->eq('id', $update->createParameter('id')));

		while ($row = $result->fetchAssociative()) {
			[$source, $credentials] = CalDavBackend::extractSubscriptionCredentials((string)$row['source']);
			if ($credentials === null) {
				continue;
			}

			$update->setParameter('source', $source)
				->setParameter('credentials', $credentials)
				->setParameter('id', (int)$row['id'], IQueryBuilder::PARAM_INT)
				->executeStatement();
		}
		$result->closeCursor();
	}
}
