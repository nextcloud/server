<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Add oc_file_metadata table
 * @see \OC\Metadata\FileMetadata
 */
class Version24000Date20220404230027 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		// /** @var ISchemaWrapper $schema */
		// $schema = $schemaClosure();

		// if (!$schema->hasTable('file_metadata')) {
		// 	$table = $schema->createTable('file_metadata');
		// 	$table->addColumn('id', Types::BIGINT, [
		// 		'notnull' => true,
		// 	]);
		// 	$table->addColumn('group_name', Types::STRING, [
		// 		'notnull' => true,
		// 		'length' => 50,
		// 	]);
		// 	$table->addColumn('value', Types::TEXT, [
		// 		'notnull' => false,
		// 		'default' => '',
		// 	]);
		// 	$table->setPrimaryKey(['id', 'group_name'], 'file_metadata_idx');

		// 	return $schema;
		// }

		return null;
	}
}
