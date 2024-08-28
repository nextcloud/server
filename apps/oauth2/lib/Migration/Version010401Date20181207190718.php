<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010401Date20181207190718 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('oauth2_clients')) {
			$table = $schema->createTable('oauth2_clients');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('name', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('redirect_uri', 'string', [
				'notnull' => true,
				'length' => 2000,
			]);
			$table->addColumn('client_identifier', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('secret', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['client_identifier'], 'oauth2_client_id_idx');
		}

		if (!$schema->hasTable('oauth2_access_tokens')) {
			$table = $schema->createTable('oauth2_access_tokens');
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'unsigned' => true,
			]);
			$table->addColumn('token_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('client_id', 'integer', [
				'notnull' => true,
			]);
			$table->addColumn('hashed_code', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$table->addColumn('encrypted_token', 'string', [
				'notnull' => true,
				'length' => 786,
			]);
			$table->setPrimaryKey(['id']);
			$table->addUniqueIndex(['hashed_code'], 'oauth2_access_hash_idx');
			$table->addIndex(['client_id'], 'oauth2_access_client_id_idx');
		}
		return $schema;
	}
}
