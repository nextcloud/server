<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use OCP\Migration\BigIntMigration;

class Version23000Date20211213203940 extends BigIntMigration {
	/**
	 * @return array Returns an array with the following structure
	 *               ['table1' => ['column1', 'column2'], ...]
	 */
	protected function getColumnsByTable() {
		return [
			'profile_config' => ['id'],
		];
	}
}
