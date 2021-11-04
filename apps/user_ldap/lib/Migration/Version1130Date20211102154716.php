<?php

declare(strict_types=1);

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
			} else {
				if ($table->hasIndex('owncloud_name_groups')) {
					$table->dropIndex('owncloud_name_groups');
					$changeSchema = true;
				}
				if (!$table->hasIndex('ldap_group_dn_hashes')) {
					$table->addUniqueIndex(['ldap_dn_hash'], 'ldap_group_dn_hashes');
					$changeSchema = true;
				}
				if (!$table->hasPrimaryKey() || ($table->getPrimaryKeyColumns() !== ['owncloud_name'])) {
					$table->dropPrimaryKey();
					$table->setPrimaryKey(['owncloud_name']);
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
		$q = $this->getSelectQuery($table);
		$u = $this->getUpdateQuery($table);

		$r = $q->executeQuery();
		while ($row = $r->fetch()) {
			$dnHash = hash('sha256', $row['ldap_dn'], false);
			$u->setParameter('name', $row['owncloud_name']);
			$u->setParameter('dn_hash', $dnHash);
			try {
				$u->executeStatement();
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
		$r->closeCursor();
	}

	protected function getSelectQuery(string $table): IQueryBuilder {
		$q = $this->dbc->getQueryBuilder();
		$q->select('owncloud_name', 'ldap_dn', 'ldap_dn_hash')
			->from($table)
			->where($q->expr()->isNull('ldap_dn_hash'));
		return $q;
	}

	protected function getUpdateQuery(string $table): IQueryBuilder {
		$q = $this->dbc->getQueryBuilder();
		$q->update($table)
			->set('ldap_dn_hash', $q->createParameter('dn_hash'))
			->where($q->expr()->eq('owncloud_name', $q->createParameter('name')));
		return $q;
	}
}
