<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DropAccountTermsTable implements IRepairStep {
	public function __construct(
		protected IDBConnection $db,
	) {
	}

	public function getName(): string {
		return 'Drop account terms table when migrating from ownCloud';
	}

	public function run(IOutput $output): void {
		if (!$this->db->tableExists('account_terms')) {
			return;
		}

		$this->db->dropTable('account_terms');
	}
}
