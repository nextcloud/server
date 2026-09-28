<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version011603Date20230620111039 extends SimpleMigrationStep {

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('oauth2_access_tokens')) {
			$table = $schema->getTable('oauth2_access_tokens');
			$dbChanged = false;
			if (!$table->hasColumn('code_created_at')) {
				$table->addColumn('code_created_at', Types::BIGINT, [
					'notnull' => true,
					'default' => 0,
					'unsigned' => true,
				]);
				$dbChanged = true;
			}
			if (!$table->hasColumn('token_count')) {
				$table->addColumn('token_count', Types::BIGINT, [
					'notnull' => true,
					'default' => 0,
					'unsigned' => true,
				]);
				$dbChanged = true;
			}
			if (!$table->hasIndex('oauth2_tk_c_created_idx')) {
				$table->addIndex(['token_count', 'code_created_at'], 'oauth2_tk_c_created_idx');
				$dbChanged = true;
			}
			if ($dbChanged) {
				return $schema;
			}
		}

		return null;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		// we consider that existing access_tokens have already produced at least one oauth token
		// which prevents cleaning them up
		$qbUpdate = $this->connection->getQueryBuilder();
		$qbUpdate->update('oauth2_access_tokens')
			->set('token_count', $qbUpdate->createNamedParameter(1, IQueryBuilder::PARAM_INT));
		$qbUpdate->executeStatement();
	}
}
