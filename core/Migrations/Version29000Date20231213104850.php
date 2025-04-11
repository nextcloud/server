<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version29000Date20231213104850 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('comments');
		$modified = false;

		if (!$table->hasColumn('reference_id')) {
			$modified = true;
			$table->addColumn('reference_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
		}

		if (!$table->hasColumn('meta_data')) {
			$modified = true;
			$table->addColumn('meta_data', Types::TEXT, [
				'notnull' => false,
				'default' => '',
			]);
		}

		return $modified ? $schema : null;
	}
}
