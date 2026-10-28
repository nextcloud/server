<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Drop index bruteforce_attempts_ip
 *
 * Attempts are only looked up by subnet, the ip column is never filtered on.
 */
#[DropIndex(table: 'bruteforce_attempts', type: IndexType::INDEX, description: 'remove unused index bruteforce_attempts_ip')]
class Version36000Date20261028100000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('bruteforce_attempts');

		// Index added in Version13000Date20170718121200
		if ($table->hasIndex('bruteforce_attempts_ip')) {
			$table->dropIndex('bruteforce_attempts_ip');
			return $schema;
		}

		return null;
	}
}
