<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\CreateTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Introduce tables storing nested-group relationships:
 *   - group_group(parent_gid, child_gid) — group G has subgroup H
 *   - group_group_admin(admin_gid, gid) — group H administers group G
 */
#[CreateTable(table: 'group_group', description: 'Parent/child edges for nested group membership')]
#[CreateTable(table: 'group_group_admin', description: 'Group-level sub-admin assignments')]
class Version34000Date20260410120000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$changed = false;

		if (!$schema->hasTable('group_group')) {
			$table = $schema->createTable('group_group');
			$table->addColumn('parent_gid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('child_gid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['parent_gid', 'child_gid']);
			$table->addIndex(['child_gid'], 'gg_child_idx');
			$changed = true;
		}

		if (!$schema->hasTable('group_group_admin')) {
			$table = $schema->createTable('group_group_admin');
			$table->addColumn('admin_gid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('gid', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$table->setPrimaryKey(['admin_gid', 'gid']);
			$table->addIndex(['gid'], 'gga_gid_idx');
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
