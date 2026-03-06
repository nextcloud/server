<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[CreateTable(table: 'dav_ocm_token_map', description: 'Maps OCM access tokens to their originating refresh tokens')]
class Version1037Date20260306120000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('dav_ocm_token_map')) {
			return null;
		}

		$table = $schema->createTable('dav_ocm_token_map');
		$table->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('access_token_id', Types::INTEGER, [
			'notnull' => true,
			'unsigned' => true,
		]);
		$table->addColumn('refresh_token', Types::STRING, [
			'notnull' => true,
			'length' => 512,
		]);
		$table->addColumn('expires', Types::INTEGER, [
			'notnull' => true,
		]);

		$table->setPrimaryKey(['id']);
		$table->addIndex(['access_token_id'], 'dav_ocm_tkmap_atid');
		$table->addIndex(['expires'], 'dav_ocm_tkmap_exp');

		return $schema;
	}
}
