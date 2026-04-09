<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_External\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version22000Date20210216084416 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('external_applicable');
		if ($table->hasIndex('applicable_type_value')) {
			$table->dropIndex('applicable_type_value');
		}

		$table = $schema->getTable('external_config');
		if ($table->hasIndex('config_mount')) {
			$table->dropIndex('config_mount');
		}

		$table = $schema->getTable('external_options');
		if ($table->hasIndex('option_mount')) {
			$table->dropIndex('option_mount');
		}

		return $schema;
	}
}
