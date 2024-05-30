<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	public function __construct(
		protected IDBConnection $connection,
	) {
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
