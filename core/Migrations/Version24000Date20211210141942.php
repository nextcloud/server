<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Email addresses are case insensitive
 * But previously a user could enter any casing of the email address
 * and we would in case of a login lower case the input and the database value.
 *
 */
class Version24000Date20211210141942 extends SimpleMigrationStep {
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
		$update = $this->connection->getQueryBuilder();

		$update->update('preferences')
			->set('configvalue', $update->func()->lower('configvalue'))
			->where($update->expr()->eq('appid', $update->createNamedParameter('settings')))
			->andWhere($update->expr()->eq('configkey', $update->createNamedParameter('email')));

		$update->executeStatement();
	}
}
