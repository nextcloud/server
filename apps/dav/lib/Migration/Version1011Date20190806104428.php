<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version1011Date20190806104428 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable('dav_cal_proxy');
		$table->addColumn('id', Types::BIGINT, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 11,
			'unsigned' => true,
		]);
		$table->addColumn('owner_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('proxy_id', Types::STRING, [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('permissions', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
			'unsigned' => true,
		]);

		$table->setPrimaryKey(['id']);
		$table->addUniqueIndex(['owner_id', 'proxy_id', 'permissions'], 'dav_cal_proxy_uidx');
		$table->addIndex(['proxy_id'], 'dav_cal_proxy_ipid');

		return $schema;
	}
}
