<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1141Date20220323143801 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $dbc,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		foreach (['ldap_user_mapping', 'ldap_group_mapping'] as $tableName) {
			$qb = $this->dbc->getQueryBuilder();
			$qb->select('ldap_dn')
				->from($tableName)
				->where($qb->expr()->gt($qb->func()->octetLength('ldap_dn'), $qb->createNamedParameter('4000'), IQueryBuilder::PARAM_INT));

			$dnsTooLong = [];
			$result = $qb->executeQuery();
			while (($dn = $result->fetchOne()) !== false) {
				$dnsTooLong[] = $dn;
			}
			$result->closeCursor();
			$this->shortenDNs($dnsTooLong, $tableName);
		}
	}

	protected function shortenDNs(array $dns, string $table): void {
		$qb = $this->dbc->getQueryBuilder();
		$qb->update($table)
			->set('ldap_dn', $qb->createParameter('shortenedDn'))
			->where($qb->expr()->eq('ldap_dn', $qb->createParameter('originalDn')));

		$pageSize = 1000;
		$page = 0;
		do {
			$subset = array_slice($dns, $page * $pageSize, $pageSize);
			try {
				$this->dbc->beginTransaction();
				foreach ($subset as $dn) {
					$shortenedDN = mb_substr($dn, 0, 4000);
					$qb->setParameter('shortenedDn', $shortenedDN);
					$qb->setParameter('originalDn', $dn);
					$qb->executeStatement();
				}
				$this->dbc->commit();
			} catch (\Throwable $t) {
				$this->dbc->rollBack();
				throw $t;
			}
			$page++;
		} while (count($subset) === $pageSize);
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

		foreach (['ldap_user_mapping', 'ldap_group_mapping'] as $tableName) {
			$table = $schema->getTable($tableName);
			$column = $table->getColumn('ldap_dn');
			if ($column->getLength() > 4000) {
				$column->setLength(4000);
			}
		}

		return $schema;
	}
}
