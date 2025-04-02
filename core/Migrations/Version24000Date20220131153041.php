<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version24000Date20220131153041 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('jobs');
		if (!$table->hasColumn('time_sensitive')) {
			$table->addColumn('time_sensitive', Types::SMALLINT, [
				'default' => 1,
			]);
			// jobs_time_sensitive replaced by jobs_sensitive_lastcheck_reserved
			// $table->addIndex(['time_sensitive'], 'jobs_time_sensitive');
			// Added later on (32 and backported)
			$table->addIndex(['time_sensitive', 'last_checked', 'reserved_at'], 'jobs_sensitive_lastcheck_reserved');
			return $schema;
		}
		return null;
	}
}
