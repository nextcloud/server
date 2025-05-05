<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropIndex;
use OCP\Migration\Attributes\IndexType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[DropIndex('jobs',	IndexType::INDEX, 'Drops redundant index', notes: ['job_argument_hash is used instead'])]
class Version32000Date20250402070341 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if (!$schema->hasTable('jobs')) {
			return null;
		}
		$jobsTable = $schema->getTable('jobs');
		if (!$jobsTable->hasIndex('job_class_index')) {
			return null;
		}

		$jobsTable->dropIndex('job_class_index');

		return $schema;
	}

}
