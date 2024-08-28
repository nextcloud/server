<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Federation\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1010Date20200630191302 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('trusted_servers')) {
			$table = $schema->createTable('trusted_servers');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('url', Types::STRING, [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('url_hash', Types::STRING, [
				'notnull' => true,
				'default' => '',
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => false,
				'length' => 128,
			]);
			$table->addColumn('shared_secret', Types::STRING, [
				'notnull' => false,
				'length' => 256,
			]);
			$table->addColumn('status', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 2,
			]);
			$table->addColumn('sync_token', Types::STRING, [
				'notnull' => false,
				'length' => 512,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['url_hash'], 'url_hash');
		}
		return $schema;
	}
}
