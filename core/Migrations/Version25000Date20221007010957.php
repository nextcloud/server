<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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

namespace OC\Core\Migrations;

use Closure;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * User background settings handling was moved from the
 * dashboard app to the theming app so we migrate the
 * respective preference values here
 *
 */
class Version25000Date20221007010957 extends SimpleMigrationStep {
	protected IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$cleanUpQuery = $this->connection->getQueryBuilder();
		$cleanUpQuery->delete('preferences')
			->where($cleanUpQuery->expr()->eq('appid', $cleanUpQuery->createNamedParameter('theming')))
			->andWhere($cleanUpQuery->expr()->orX(
				$cleanUpQuery->expr()->eq('configkey', $cleanUpQuery->createNamedParameter('background')),
				$cleanUpQuery->expr()->eq('configkey', $cleanUpQuery->createNamedParameter('backgroundVersion')),
			));
		$cleanUpQuery->executeStatement();

		$updateQuery = $this->connection->getQueryBuilder();
		$updateQuery->update('preferences')
			->set('appid', $updateQuery->createNamedParameter('theming'))
			->where($updateQuery->expr()->eq('appid', $updateQuery->createNamedParameter('dashboard')))
			->andWhere($updateQuery->expr()->orX(
				$updateQuery->expr()->eq('configkey', $updateQuery->createNamedParameter('background')),
				$updateQuery->expr()->eq('configkey', $updateQuery->createNamedParameter('backgroundVersion')),
			));
		$updateQuery->executeStatement();
	}
}
