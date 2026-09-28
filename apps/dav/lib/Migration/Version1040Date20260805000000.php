<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCA\DAV\CalDAV\Federation\FederatedCalendarEntity;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'calendars_federated', name: 'state', type: ColumnType::INTEGER, description: 'Invitation state of the incoming federated calendar share (0 = pending, 1 = accepted)')]
class Version1040Date20260805000000 extends SimpleMigrationStep {
	private bool $backfillState = false;

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('calendars_federated');

		if (!$table->hasColumn('state')) {
			$table->addColumn('state', Types::INTEGER, [
				'notnull' => true,
				'unsigned' => true,
				'default' => FederatedCalendarEntity::STATE_PENDING,
			]);
			$this->backfillState = true;
		}

		return $schema;
	}

	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if (!$this->backfillState) {
			return;
		}

		// Calendars shared before the invitation flow was introduced were
		// never explicitly accepted, keep them visible
		$qb = $this->connection->getQueryBuilder();
		$qb->update('calendars_federated')
			->set('state', $qb->createNamedParameter(FederatedCalendarEntity::STATE_ACCEPTED, IQueryBuilder::PARAM_INT));
		$qb->executeStatement();
	}
}
