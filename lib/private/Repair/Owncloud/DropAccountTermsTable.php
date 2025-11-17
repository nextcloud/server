<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair\Owncloud;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DropAccountTermsTable implements IRepairStep {
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(
		protected IDBConnection $db,
	) {
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Drop account terms table when migrating from ownCloud';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		if (!$this->db->tableExists('account_terms')) {
			return;
		}

		$this->db->dropTable('account_terms');
	}
}
