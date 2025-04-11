<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Migration;

use OCP\Migration\BigIntMigration;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1002Date20170926101419 extends BigIntMigration {

	/**
	 * @return array Returns an array with the following structure
	 *               ['table1' => ['column1', 'column2'], ...]
	 * @since 13.0.0
	 */
	protected function getColumnsByTable() {
		return [
			'twofactor_backupcodes' => ['id'],
		];
	}
}
