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

final class Version2000Date20261013142538 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		$shareTable = $schema->getTable('sharing_share');
		$userStatusTable = $schema->getTable('sharing_share_user_status');
		$changed = false;

		if ($shareTable->hasColumn('state')) {
			$shareTable->dropColumn('state');
			$changed = true;
		}

		if (!$shareTable->hasColumn('state_id')) {
			$shareTable->addColumn('state_id', Types::INTEGER);
			$changed = true;
		}

		if ($userStatusTable->hasColumn('status')) {
			$userStatusTable->dropColumn('status');
			$changed = true;
		}

		if (!$userStatusTable->hasColumn('status_id')) {
			$userStatusTable->addColumn('status_id', Types::INTEGER);
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
