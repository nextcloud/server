<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1002Date20170607113030 extends SimpleMigrationStep {

	/**
	 * @param IDBConnection $connection
	 */
	public function __construct(
		protected IDBConnection $connection,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('twofactor_backup_codes')) {
			// Legacy table does not exist
			return;
		}

		$insert = $this->connection->getQueryBuilder();
		$insert->insert('twofactor_backupcodes')
			->values([
				// Inserting with id might fail: 'id' => $insert->createParameter('id'),
				'user_id' => $insert->createParameter('user_id'),
				'code' => $insert->createParameter('code'),
				'used' => $insert->createParameter('used'),
			]);

		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from('twofactor_backup_codes')
			->orderBy('id', 'ASC');
		$result = $query->execute();

		$output->startProgress();
		while ($row = $result->fetch()) {
			$output->advance();

			$insert
				// Inserting with id might fail: ->setParameter('id', $row['id'], IQueryBuilder::PARAM_INT)
				->setParameter('user_id', $row['user_id'], IQueryBuilder::PARAM_STR)
				->setParameter('code', $row['code'], IQueryBuilder::PARAM_STR)
				->setParameter('used', $row['used'], IQueryBuilder::PARAM_INT)
				->execute();
		}
		$output->finishProgress();
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @since 13.0.0
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('twofactor_backup_codes')) {
			$schema->dropTable('twofactor_backup_codes');
			return $schema;
		}
		return null;
	}
}
