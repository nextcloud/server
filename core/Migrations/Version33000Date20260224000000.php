<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add time_sensitive column to the job_lastcheck_reserved index on oc_jobs.
 *
 * The cron background job scheduler query filters on three columns:
 *   reserved_at <= ?, last_checked <= ?, AND time_sensitive = ?
 *
 * The old index only covered last_checked and reserved_at, forcing the
 * database to do a full table scan to evaluate the time_sensitive predicate.
 * Adding time_sensitive to the index allows the database to evaluate all
 * three conditions using the index alone (covering index scan), significantly
 * improving performance when a maintenance window is configured.
 *
 * @see https://github.com/nextcloud/server/issues/46126
 */
class Version33000Date20260224000000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('jobs')) {
			return null;
		}

		$table = $schema->getTable('jobs');

		// Drop the old index that only covers last_checked and reserved_at
		if ($table->hasIndex('job_lastcheck_reserved')) {
			$table->dropIndex('job_lastcheck_reserved');
		}

		// Recreate the index with time_sensitive included.
		// This allows the background job scheduler query to use a full index scan
		// instead of a partial index + row lookup, improving performance when
		// a maintenance window restricts which jobs are eligible to run.
		if (!$table->hasIndex('job_lastcheck_timesens')) {
			$table->addIndex(
				['last_checked', 'reserved_at', 'time_sensitive'],
				'job_lastcheck_timesens',
			);
		}

		return $schema;
	}
}
