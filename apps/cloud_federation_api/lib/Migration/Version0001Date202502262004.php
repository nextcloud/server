<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date202502262004 extends SimpleMigrationStep
{

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
	{
		/** @var ISchemaWrapper $schema */


		$schema = $schemaClosure();
		$table_name = 'federated_invites';

		if (! $schema->hasTable($table_name)) {

			$table = $schema->createTable($table_name);
			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 20,
				'unsigned' => true,
			]);

			$table->addColumn('user_id', 'bigint', [
				'notnull' => false,
				'length' => 20,
				'unsigned' => true,

			]);


			$table->addColumn('token', 'string', [
				'notnull' => true,
				'length' => 60,
			]);
			$table->addColumn('email', 'string', [
				'notnull' => true,
				'length' => 256,
			]);
			$table->addColumn('accepted', 'boolean', [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('createdAt', 'datetime', [
				'notnull' => true,
			]);

			$table->addColumn('expiredAt', 'datetime', [
				'notnull' => false,
			]);

			$table->addColumn('acceptedAt', 'datetime', [
				'notnull' => false,
			]);


			$table->setPrimaryKey(['id']);
		}

		return $schema;
	}
}
