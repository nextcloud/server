<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OC\SystemTag\SystemTagObjectMapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Removes system tag assignments that were stored under a tag id that is not an integer
 */
class RemoveInvalidSystemTagMappings implements IRepairStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Remove system tag assignments with an invalid tag id';
	}

	#[\Override]
	public function run(IOutput $output): void {
		if ($this->connection->getDatabaseProvider() !== IDBConnection::PLATFORM_SQLITE) {
			// only SQLite stores a value that is not an integer in an integer column
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query->delete(SystemTagObjectMapper::RELATION_TABLE)
			->where($query->expr()->neq(
				'systemtagid',
				$query->createFunction('CAST(' . $query->getColumnName('systemtagid') . ' AS INTEGER)')
			));

		$deletedEntries = $query->executeStatement();
		if ($deletedEntries > 0) {
			$output->info('Removed ' . $deletedEntries . ' system tag assignments with an invalid tag id');
		}
	}
}
