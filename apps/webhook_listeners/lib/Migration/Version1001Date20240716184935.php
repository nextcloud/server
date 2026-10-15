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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version1001Date20240716184935 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable(WebhookListenerMapper::TABLE_NAME)) {
			$table = $schema->getTable(WebhookListenerMapper::TABLE_NAME);
			$table->getColumn('user_id')->setNotnull(false)->setDefault(null);
			return $schema;
		}
		return null;
	}
}
