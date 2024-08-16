<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\WorkflowEngine\Migration;

use OCP\DB\IResult;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\WorkflowEngine\IManager;

class PopulateNewlyIntroducedDatabaseFields implements IRepairStep {

	/** @var IDBConnection */
	private $dbc;

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
	}

	public function getName() {
		return 'Populating added database structures for workflows';
	}

	public function run(IOutput $output) {
		$result = $this->getIdsWithoutScope();

		$this->populateScopeTable($result);

		$result->closeCursor();
	}

	protected function populateScopeTable(IResult $ids): void {
		$qb = $this->dbc->getQueryBuilder();

		$insertQuery = $qb->insert('flow_operations_scope');
		while (($id = $ids->fetchOne()) !== false) {
			$insertQuery->values(['operation_id' => $qb->createNamedParameter($id), 'type' => IManager::SCOPE_ADMIN]);
			$insertQuery->execute();
		}
	}

	protected function getIdsWithoutScope(): IResult {
		$qb = $this->dbc->getQueryBuilder();
		$selectQuery = $qb->select('o.id')
			->from('flow_operations', 'o')
			->leftJoin('o', 'flow_operations_scope', 's', $qb->expr()->eq('o.id', 's.operation_id'))
			->where($qb->expr()->isNull('s.operation_id'));
		// The left join operation is not necessary, usually, but it's a safe-guard
		// in case the repair step is executed multiple times for whatever reason.

		/** @var IResult $result */
		$result = $selectQuery->execute();
		return $result;
	}
}
