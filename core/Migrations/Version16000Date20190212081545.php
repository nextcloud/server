<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version16000Date20190212081545 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('login_flow_v2');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('timestamp', Types::BIGINT, [
			'notnull' => true,
			'length' => 20,
			'unsigned' => true,
		]);
		$table->addColumn('started', Types::SMALLINT, [
			'notnull' => true,
			'length' => 1,
			'unsigned' => true,
			'default' => 0,
		]);
		$table->addColumn('poll_token', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('login_token', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('public_key', Types::TEXT, [
			'notnull' => true,
			'length' => 32768,
		]);
		$table->addColumn('private_key', Types::TEXT, [
			'notnull' => true,
			'length' => 32768,
		]);
		$table->addColumn('client_name', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$table->addColumn('login_name', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('server', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('app_password', Types::STRING, [
			'notnull' => false,
			'length' => 1024,
		]);
		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['poll_token'], 'poll_token');
		$table->addUniqueIndex(['login_token'], 'login_token');
		$table->addIndex(['timestamp'], 'timestamp');

		return $schema;
	}
}
