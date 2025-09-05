<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Migration;

use Closure;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1000Date20240527153425 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable(WebhookListenerMapper::TABLE_NAME)) {
			$table = $schema->createTable(WebhookListenerMapper::TABLE_NAME);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('app_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('http_method', Types::STRING, [
				'notnull' => true,
				'length' => 32,
			]);
			$table->addColumn('uri', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('event', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
			]);
			$table->addColumn('event_filter', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('user_id_filter', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('headers', Types::TEXT, [
				'notnull' => false,
			]);
			$table->addColumn('auth_method', Types::STRING, [
				'notnull' => true,
				'length' => 16,
				'default' => '',
			]);
			$table->addColumn('auth_data', Types::TEXT, [
				'notnull' => false,
			]);
			$table->setPrimaryKey(['id']);
			return $schema;
		}
		return null;
	}
}
