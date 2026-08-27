<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version1000Date20260826073021 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('sharing_share_user_status')) {
			$shareTable = $schema->getTable('sharing_share');

			$userStatusTable = $schema->createTable('sharing_share_user_status');
			$userStatusTable->addColumn('share_id', Types::BIGINT);
			$userStatusTable->addColumn('user_id', Types::STRING, ['length' => 64]);
			$userStatusTable->addColumn('status', Types::STRING, ['length' => 16]);
			$userStatusTable->setPrimaryKey(['share_id', 'user_id']);
			$userStatusTable->addForeignKeyConstraint($shareTable->getName(), ['share_id'], ['id'], ['onDelete' => 'CASCADE']);
		}

		return $schema;
	}
}
