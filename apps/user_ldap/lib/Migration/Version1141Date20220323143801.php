<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1141Date20220323143801 extends SimpleMigrationStep {

	private IDBConnection $dbc;

	public function __construct(IDBConnection $dbc) {
		$this->dbc = $dbc;
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
