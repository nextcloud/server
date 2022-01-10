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

	public function __construct(IDBConnection $dbc, LoggerInterface $logger) {
		$this->dbc = $dbc;
		$this->logger = $logger;
	}

	public function getName() {
		return 'Adjust LDAP user and group ldap_dn column lengths and add ldap_dn_hash columns';
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
			}
			$column = $table->getColumn('ldap_dn');
			if ($column->getLength() < 4096) {
				$column->setLength(4096);
				$changeSchema = true;
			}
			if ($tableName === 'ldap_user_mapping') {
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
			} else {
				if ($table->hasIndex('owncloud_name_groups')) {
					$table->dropIndex('owncloud_name_groups');
					$changeSchema = true;
				}
				if (!$table->hasIndex('ldap_group_dn_hashes')) {
					$table->addUniqueIndex(['ldap_dn_hash'], 'ldap_group_dn_hashes');
					$changeSchema = true;
				}
				if (!$table->hasIndex('ldap_group_directory_uuid')) {
					$table->addUniqueIndex(['directory_uuid'], 'ldap_group_directory_uuid');
					$changeSchema = true;
				}
				if (!$table->hasPrimaryKey()) {
					$table->setPrimaryKey(['owncloud_name']);
					$changeSchema = true;
				}

				if ($table->getPrimaryKeyColumns() !== ['owncloud_name']) {
					// We need to copy the table twice to be able to change primary key, prepare the backup table
					$table2 = $schema->createTable('ldap_group_mapping_backup');
					$table2->addColumn('ldap_dn', Types::STRING, [
						'notnull' => true,
						'length' => 255,
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
					$table2->setPrimaryKey(['owncloud_name']);
					$table2->addUniqueIndex(['ldap_dn_hash'], 'ldap_group_dn_hashes');
					$table2->addUniqueIndex(['directory_uuid'], 'ldap_group_directory_uuid');
					$changeSchema = true;
				}
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

		$result = $select->execute();
		while ($row = $result->fetch()) {
			$dnHash = hash('sha256', $row['ldap_dn'], false);
			$update->setParameter('name', $row['owncloud_name']);
			$update->setParameter('dn_hash', $dnHash);
			try {
				$update->execute();
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
		$qb->select('owncloud_name', 'ldap_dn', 'ldap_dn_hash')
			->from($table)
			->where($qb->expr()->isNull('ldap_dn_hash'));
		return $qb;
	}

	protected function getUpdateQuery(string $table): IQueryBuilder {
		$qb = $this->dbc->getQueryBuilder();
		$qb->update($table)
			->set('ldap_dn_hash', $qb->createParameter('dn_hash'))
			->where($qb->expr()->eq('owncloud_name', $qb->createParameter('name')));
		return $qb;
	}
}
