<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Migration;

use Closure;
use OCA\WebhookListeners\Db\EphemeralTokenMapper;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
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
		$schemaHasChanged = false;

		if ($schema->hasTable(WebhookListenerMapper::TABLE_NAME)) {
			$table = $schema->getTable(WebhookListenerMapper::TABLE_NAME);
			if (!$table->hasColumn('token_needed')) {
				$schemaHasChanged = true;
				$table->addColumn('token_needed', Types::TEXT, [
					'notnull' => false,
				]);
			}
		}

		if (!$schema->hasTable(EphemeralTokenMapper::TABLE_NAME)) {
			$schemaHasChanged = true;
			$table = $schema->createTable(EphemeralTokenMapper::TABLE_NAME);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
			]);
			$table->addColumn('token_id', Types::BIGINT, [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('created_at', Types::BIGINT, [
				'notnull' => true,
				'length' => 4,
				'unsigned' => true,
			]);
			$table->setPrimaryKey(['id']);
		}
		return $schemaHasChanged ? $schema : null;
	}
}
