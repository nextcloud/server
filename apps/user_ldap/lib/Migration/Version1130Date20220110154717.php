<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\User_LDAP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;

class Version1130Date20220110154717 extends GroupMappingMigration {
	public function getName() {
		return 'Copy ldap_group_mapping data to backup table if needed';
	}

	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @since 13.0.0
	 */
	public function preSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ldap_group_mapping_backup')) {
			// Backup table does not exist
			return;
		}

		$output->startProgress();
		$this->copyGroupMappingData('ldap_group_mapping', 'ldap_group_mapping_backup');
		$output->finishProgress();
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ldap_group_mapping_backup')) {
			// Backup table does not exist
			return null;
		}

		$schema->dropTable('ldap_group_mapping');

		return $schema;
	}
}
