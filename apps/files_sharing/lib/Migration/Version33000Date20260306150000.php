<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Drop the now-unused `share_external.share_token` column. The data was copied
 * to the `refresh_token` column by Version33000Date20260306140000.
 */
#[DropColumn(table: 'share_external', name: 'share_token', description: 'Renamed to refresh_token')]
class Version33000Date20260306150000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('share_external');

		if (!$table->hasColumn('share_token')) {
			return null;
		}

		$table->dropColumn('share_token');

		return $schema;
	}
}
