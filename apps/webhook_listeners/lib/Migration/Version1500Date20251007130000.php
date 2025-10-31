<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Migration;

use Closure;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCA\WebhookListeners\Db\TemporaryTokenMapper;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1500Date20251007130000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable(WebhookListenerMapper::TABLE_NAME)) {
			$table = $schema->getTable(WebhookListenerMapper::TABLE_NAME);
			if (!$table->hasColumn('token_needed')) {
				$table->addColumn('token_needed', Types::TEXT, [
					'notnull' => false,
				]);
			}
		}

		if (!$schema->hasTable(TemporaryTokenMapper::TABLE_NAME)) {
			$table = $schema->createTable(TemporaryTokenMapper::TABLE_NAME);
			$table->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
			]);
			
			$table->addColumn('token_id', 'integer', [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('token', 'string', [
				'notnull' => false,
				'length' => 200,
			]);
			$table->addColumn('user_id', 'string', [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('creation_datetime', 'integer', [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			
			$table->setPrimaryKey(['id']);
			
		}
		return $schema;

	}
}
