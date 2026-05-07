<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Migration;

use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Drops the federated_invites table unless it contains data.
 */
class DropFederatedInvitesTable implements IRepairStep {

	public function __construct(
		protected Connection $db,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Conditionally drop the federated_invites table';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$table_name = 'federated_invites';
		$schema = new SchemaWrapper($this->db);
		if (!$schema->hasTable($table_name)) {
			echo("$table_name does not exist");
			return;
		}
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($table_name)
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$hasRows = $result->fetchOne();
		if (!$hasRows) {
			$schema->dropTable($table_name);
			$schema->performDropTableCalls();
			$output->info('Table federated_invites dropped');
		} else {
			$output->info('Table federated_invites contains data. Table will be kept.');
		}
	}
}
