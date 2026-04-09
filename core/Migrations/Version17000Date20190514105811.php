<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version17000Date20190514105811 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if (!$schema->hasTable('filecache_extended')) {
			$table = $schema->createTable('filecache_extended');
			$table->addColumn('fileid', Types::BIGINT, [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('metadata_etag', Types::STRING, [
				'notnull' => false,
				'length' => 40,
			]);
			$table->addColumn('creation_time', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
				'default' => 0,
			]);
			$table->addColumn('upload_time', Types::BIGINT, [
				'notnull' => true,
				'length' => 20,
				'default' => 0,
			]);
			$table->setPrimaryKey(['fileid'], 'fce_pk');
			//			$table->addUniqueIndex(['fileid'], 'fce_fileid_idx');
			$table->addIndex(['creation_time'], 'fce_ctime_idx');
			$table->addIndex(['upload_time'], 'fce_utime_idx');
		}

		return $schema;
	}
}
