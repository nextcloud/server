<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version22000Date20210216080825 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('appconfig');
		if ($table->hasIndex('appconfig_appid_key')) {
			$table->dropIndex('appconfig_appid_key');
		}

		$table = $schema->getTable('collres_accesscache');
		if ($table->hasIndex('collres_user_coll')) {
			$table->dropIndex('collres_user_coll');
		}

		$table = $schema->getTable('mounts');
		if ($table->hasIndex('mounts_user_index')) {
			$table->dropIndex('mounts_user_index');
		}

		return $schema;
	}
}
