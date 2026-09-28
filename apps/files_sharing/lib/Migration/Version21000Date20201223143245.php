<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version21000Date20201223143245 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('share_external')) {
			$table = $schema->getTable('share_external');
			$changed = false;
			if (!$table->hasColumn('parent')) {
				$table->addColumn('parent', Types::BIGINT, [
					'notnull' => false,
					'default' => -1,
				]);
				$changed = true;
			}
			if (!$table->hasColumn('share_type')) {
				$table->addColumn('share_type', Types::INTEGER, [
					'notnull' => false,
					'length' => 4,
				]);
				$changed = true;
			}
			if ($table->hasColumn('lastscan')) {
				$table->dropColumn('lastscan');
				$changed = true;
			}

			if ($changed) {
				return $schema;
			}
		}

		return null;
	}
}
