<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version11300Date20201120141438 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('share_external')) {
			$table = $schema->createTable('share_external');
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('parent', Types::BIGINT, [
				'notnull' => false,
				'default' => -1,
			]);
			$table->addColumn('share_type', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
			$table->addColumn('remote', Types::STRING, [
				'notnull' => true,
				'length' => 512,
			]);
			$table->addColumn('remote_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('share_token', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('password', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('owner', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('user', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('mountpoint', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('mountpoint_hash', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('accepted', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['user', 'mountpoint_hash'], 'sh_external_mp');
		} else {
			$table = $schema->getTable('share_external');
			$remoteIdColumn = $table->getColumn('remote_id');
			if ($remoteIdColumn && $remoteIdColumn->getType()->getName() !== Types::STRING) {
				$remoteIdColumn->setNotnull(false);
				$remoteIdColumn->setType(Type::getType(Types::STRING));
				$remoteIdColumn->setOptions(['length' => 255]);
				$remoteIdColumn->setDefault('');
			}
			if (!$table->hasColumn('parent')) {
				$table->addColumn('parent', Types::BIGINT, [
					'notnull' => false,
					'default' => -1,
				]);
			}
			if (!$table->hasColumn('share_type')) {
				$table->addColumn('share_type', Types::INTEGER, [
					'notnull' => false,
					'length' => 4,
				]);
			}
			if ($table->hasColumn('lastscan')) {
				$table->dropColumn('lastscan');
			}
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		$qb = $this->connection->getQueryBuilder();
		$qb->update('share_external')
			->set('remote_id', $qb->createNamedParameter(''))
			->where($qb->expr()->eq('remote_id', $qb->createNamedParameter('-1')));
		$qb->execute();
	}
}
