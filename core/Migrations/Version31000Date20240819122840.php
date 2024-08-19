<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version31000Date20240819122840 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('jobs');

		# Remove previous indices
		if ($table->hasIndex('job_lastcheck_reserved')) {
			$table->dropIndex('job_lastcheck_reserved');
		}
		if ($table->hasIndex('jobs_time_sensitive')) {
			$table->dropIndex('jobs_time_sensitive');
		}

		# Add updated index
		if (!$table->hasIndex('job_last_reserved_sensitive')) {
			$table->addIndex(['last_checked', 'reserved_at', 'time_sensitive'], 'job_last_reserved_sensitive');
		}

		return $schema;
	}
}
