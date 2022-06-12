<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\Migration;

use Closure;
use Generator;
use OCP\DB\Exception;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version1130Date20211102154716 extends SimpleMigrationStep {

	/** @var IDBConnection */
	private $dbc;
	/** @var LoggerInterface */
	private $logger;
	/** @var string[] */
	private $hashColumnAddedToTables = [];

	public function __construct(IDBConnection $dbc, LoggerInterface $logger) {
		$this->dbc = $dbc;
		$this->logger = $logger;
	}

	public function getName() {
		return 'Adjust LDAP user and group ldap_dn column lengths and add ldap_dn_hash columns';
	}

	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		foreach (['ldap_user_mapping', 'ldap_group_mapping'] as $tableName) {
			$this->processDuplicateUUIDs($tableName);
		}

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		if ($schema->hasTable('ldap_group_mapping_backup')) {
			// Previous upgrades of a broken release might have left an incomplete
			// ldap_group_mapping_backup table. No need to recreate, but it
			// should be empty.
			// TRUNCATE is not available from Query Builder, but faster than DELETE FROM.
			$sql = $this->dbc->getDatabasePlatform()->getTruncateTableSQL('`*PREFIX*ldap_group_mapping_backup`', false);
			$this->dbc->executeStatement($sql);
		}
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$changeSchema = false;
		foreach (['ldap_user_mapping', 'ldap_group_mapping'] as $tableName) {
			$table = $schema->getTable($tableName);
			if (!$table->hasColumn('ldap_dn_hash')) {
				$table->addColumn('ldap_dn_hash', Types::STRING, [
					'notnull' => false,
					'length' => 64,
				]);
				$changeSchema = true;
				$this->hashColumnAddedToTables[] = $tableName;
			}
			$column = $table->getColumn('ldap_dn');
			if ($tableName === 'ldap_user_mapping') {
				if ($column->getLength() < 4000) {
					$column->setLength(4000);
					$changeSchema = true;
				}

				if ($table->hasIndex('ldap_dn_users')) {
					$table->dropIndex('ldap_dn_users');
					$changeSchema = true;
				}
				if (!$table->hasIndex('ldap_user_dn_hashes')) {
					$table->addUniqueIndex(['ldap_dn_hash'], 'ldap_user_dn_hashes');
					$changeSchema = true;
				}
				if (!$table->hasIndex('ldap_user_directory_uuid')) {
					$table->addUniqueIndex(['directory_uuid'], 'ldap_user_directory_uuid');
					$changeSchema = true;
				}
			} elseif (!$schema->hasTable('ldap_group_mapping_backup')) {
				// We need to copy the table twice to be able to change primary key, prepare the backup table
				$table2 = $schema->createTable('ldap_group_mapping_backup');
				$table2->addColumn('ldap_dn', Types::STRING, [
					'notnull' => true,
					'length' => 4000,
					'default' => '',
				]);
				$table2->addColumn('owncloud_name', Types::STRING, [
					'notnull' => true,
					'length' => 64,
					'default' => '',
				]);
				$table2->addColumn('directory_uuid', Types::STRING, [
					'notnull' => true,
					'length' => 255,
					'default' => '',
				]);
				$table2->addColumn('ldap_dn_hash', Types::STRING, [
					'notnull' => false,
					'length' => 64,
				]);
				$table2->setPrimaryKey(['owncloud_name'], 'lgm_backup_primary');
				$changeSchema = true;
			}
		}

		return $changeSchema ? $schema : null;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		$this->handleDNHashes('ldap_group_mapping');
		$this->handleDNHashes('ldap_user_mapping');
	}

	protected function handleDNHashes(string $table): void {
		$select = $this->getSelectQuery($table);
		$update = $this->getUpdateQuery($table);

		$result = $select->executeQuery();
		while ($row = $result->fetch()) {
			$dnHash = hash('sha256', $row['ldap_dn'], false);
			$update->setParameter('name', $row['owncloud_name']);
			$update->setParameter('dn_hash', $dnHash);
			try {
				$update->executeStatement();
			} catch (Exception $e) {
				$this->logger->error('Failed to add hash "{dnHash}" ("{name}" of {table})',
					[
						'app' => 'user_ldap',
						'name' => $row['owncloud_name'],
						'dnHash' => $dnHash,
						'table' => $table,
						'exception' => $e,
					]
				);
			}
		}
		$result->closeCursor();
	}

	protected function getSelectQuery(string $table): IQueryBuilder {
		$qb = $this->dbc->getQueryBuilder();
		$qb->select('owncloud_name', 'ldap_dn')
			->from($table);

		// when added we may run into risk that it's read from a DB node
		// where the column is not present. Then the where clause is also
		// not necessary since all rows qualify.
		if (!in_array($table, $this->hashColumnAddedToTables, true)) {
			$qb->where($qb->expr()->isNull('ldap_dn_hash'));
		}

		return $qb;
	}

	protected function getUpdateQuery(string $table): IQueryBuilder {
		$qb = $this->dbc->getQueryBuilder();
		$qb->update($table)
			->set('ldap_dn_hash', $qb->createParameter('dn_hash'))
			->where($qb->expr()->eq('owncloud_name', $qb->createParameter('name')));
		return $qb;
	}

	/**
	 * @throws Exception
	 */
	protected function processDuplicateUUIDs(string $table): void {
		$uuids = $this->getDuplicatedUuids($table);
		$idsWithUuidToInvalidate = [];
		foreach ($uuids as $uuid) {
			array_push($idsWithUuidToInvalidate, ...$this->getNextcloudIdsByUuid($table, $uuid));
		}
		$this->invalidateUuids($table, $idsWithUuidToInvalidate);
	}

	/**
	 * @throws Exception
	 */
	protected function invalidateUuids(string $table, array $idList): void {
		$update = $this->dbc->getQueryBuilder();
		$update->update($table)
			->set('directory_uuid', $update->createParameter('invalidatedUuid'))
			->where($update->expr()->eq('owncloud_name', $update->createParameter('nextcloudId')));

		while ($nextcloudId = array_shift($idList)) {
			$update->setParameter('nextcloudId', $nextcloudId);
			$update->setParameter('invalidatedUuid', 'invalidated_' . \bin2hex(\random_bytes(6)));
			try {
				$update->executeStatement();
				$this->logger->warning(
					'LDAP user or group with ID {nid} has a duplicated UUID value which therefore was invalidated. You may double-check your LDAP configuration and trigger an update of the UUID.',
					[
						'app' => 'user_ldap',
						'nid' => $nextcloudId,
					]
				);
			} catch (Exception $e) {
				// Catch possible, but unlikely duplications if new invalidated errors.
				// There is the theoretical chance of an infinity loop is, when
				// the constraint violation has a different background. I cannot
				// think of one at the moment.
				if ($e->getReason() !== Exception::REASON_CONSTRAINT_VIOLATION) {
					throw $e;
				}
				$idList[] = $nextcloudId;
			}
		}
	}

	/**
	 * @throws \OCP\DB\Exception
	 * @return array<string>
	 */
	protected function getNextcloudIdsByUuid(string $table, string $uuid): array {
		$select = $this->dbc->getQueryBuilder();
		$select->select('owncloud_name')
			->from($table)
			->where($select->expr()->eq('directory_uuid', $select->createNamedParameter($uuid)));

		$result = $select->executeQuery();
		$idList = [];
		while (($id = $result->fetchOne()) !== false) {
			$idList[] = $id;
		}
		$result->closeCursor();
		return $idList;
	}

	/**
	 * @return Generator<string>
	 * @throws \OCP\DB\Exception
	 */
	protected function getDuplicatedUuids(string $table): Generator {
		$select = $this->dbc->getQueryBuilder();
		$select->select('directory_uuid')
			->from($table)
			->groupBy('directory_uuid')
			->having($select->expr()->gt($select->func()->count('owncloud_name'), $select->createNamedParameter(1)));

		$result = $select->executeQuery();
		while (($uuid = $result->fetchOne()) !== false) {
			yield $uuid;
		}
		$result->closeCursor();
	}
}
