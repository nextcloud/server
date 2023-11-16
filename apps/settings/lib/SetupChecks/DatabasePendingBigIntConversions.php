<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
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
namespace OCA\Settings\SetupChecks;

use Doctrine\DBAL\Types\BigIntType;
use OC\Core\Command\Db\ConvertFilecacheBigInt;
use OC\DB\Connection;
use OC\DB\SchemaWrapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class DatabasePendingBigIntConversions implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private Connection $db,
		private IEventDispatcher $dispatcher,
		private IDBConnection $connection,
	) {
	}

	public function getCategory(): string {
		return 'database';
	}

	public function getName(): string {
		return $this->l10n->t('Database pending bigint migrations');
	}

	protected function getBigIntConversionPendingColumns(): array {
		$tables = ConvertFilecacheBigInt::getColumnsByTable();

		$schema = new SchemaWrapper($this->db);
		$isSqlite = $this->connection->getDatabaseProvider() === IDBConnection::PLATFORM_SQLITE;
		$pendingColumns = [];

		foreach ($tables as $tableName => $columns) {
			if (!$schema->hasTable($tableName)) {
				continue;
			}

			$table = $schema->getTable($tableName);
			foreach ($columns as $columnName) {
				$column = $table->getColumn($columnName);
				$isAutoIncrement = $column->getAutoincrement();
				$isAutoIncrementOnSqlite = $isSqlite && $isAutoIncrement;
				if (!($column->getType() instanceof BigIntType) && !$isAutoIncrementOnSqlite) {
					$pendingColumns[] = $tableName . '.' . $columnName;
				}
			}
		}

		return $pendingColumns;
	}

	public function run(): SetupResult {
		$pendingColumns = $this->getBigIntConversionPendingColumns();
		if (empty($pendingColumns)) {
			return SetupResult::success('None');
		} else {
			$list = '';
			foreach ($pendingColumns as $pendingColumn) {
				$list .= "\n$pendingColumn";
			}
			$list .= "\n";
			return SetupResult::info(
				$this->l10n->t('Some columns in the database are missing a conversion to big int. Due to the fact that changing column types on big tables could take some time they were not changed automatically. By running "occ db:convert-filecache-bigint" those pending changes could be applied manually. This operation needs to be made while the instance is offline.').$list,
				$this->urlGenerator->linkToDocs('admin-bigint-conversion')
			);
		}
	}
}
