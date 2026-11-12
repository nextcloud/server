<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version35000Date20260910082613 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$sourceNodeTargetTable = $schema->createTable('sharing_source_node_target');
		$sourceNodeTargetTable->addColumn('user_id', Types::STRING, ['length' => 64]);
		// Can't add a FK on sharing_instance_map here, because that table is created by the sharing app.
		$sourceNodeTargetTable->addColumn('source_instance_id', Types::INTEGER, ['notnull' => false]);
		$sourceNodeTargetTable->addColumn('source_node_id', Types::INTEGER);
		$sourceNodeTargetTable->addColumn('target', Types::TEXT, ['length' => 255]);
		// Primary key doesn't work, because that makes source_instance NOT NULL
		$sourceNodeTargetTable->addUniqueIndex(['user_id', 'source_instance_id', 'source_node_id']);

		return $schema;
	}
}
