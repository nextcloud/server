<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migrate oc_file_metadata.metadata as JSON type to oc_file_metadata.value a STRING type
 * @see \OC\Metadata\FileMetadata
 */
class Version27000Date20230309104802 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		// /** @var ISchemaWrapper $schema */
		// $schema = $schemaClosure();
		// $metadataTable = $schema->getTable('file_metadata');

		// if ($metadataTable->hasColumn('metadata')) {
		// 	$metadataTable->dropColumn('metadata');
		// 	return $schema;
		// }

		return null;
	}
}
