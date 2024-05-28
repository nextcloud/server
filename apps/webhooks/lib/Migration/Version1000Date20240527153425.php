<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Migration;

use Closure;
use OCA\Webhooks\Db\WebhookListenerMapper;
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
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
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
				'length' => 256,
			]);
			$table->addColumn('event', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('headers', Types::TEXT, [
				'notnull' => false,
			]);
			// TODO decide if string or int with an Enum
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
