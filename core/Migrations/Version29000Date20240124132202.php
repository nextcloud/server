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

/**
 * Create new column and index for lazy loading in appconfig for the new IAppConfig API.
 */
class Version29000Date20240124132202 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('appconfig');
		$table->addColumn('lazy', Types::SMALLINT, ['notnull' => true, 'default' => 0, 'length' => 1, 'unsigned' => true]);

		/**
		 * we only need an index on lazy, the group 'appid'+'configkey' already
		 * exists as primary ({@see Version13000Date20170718121200})
		 */
		$table->addIndex(['lazy'], 'ac_lazy_i');

		return $schema;
	}
}
