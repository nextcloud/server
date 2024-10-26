<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1010Date20200630192842 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('ldap_user_mapping')) {
			$table = $schema->createTable('ldap_user_mapping');
			$table->addColumn('ldap_dn', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
				'default' => '',
			]);
			$table->addColumn('owncloud_name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('directory_uuid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('ldap_dn_hash', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->setPrimaryKey(['owncloud_name']);
			$table->addUniqueIndex(['ldap_dn_hash'], 'ldap_user_dn_hashes');
			$table->addUniqueIndex(['directory_uuid'], 'ldap_user_directory_uuid');
		}

		if (!$schema->hasTable('ldap_group_mapping')) {
			$table = $schema->createTable('ldap_group_mapping');
			$table->addColumn('ldap_dn', Types::STRING, [
				'notnull' => true,
				'length' => 4000,
				'default' => '',
			]);
			$table->addColumn('owncloud_name', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('directory_uuid', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('ldap_dn_hash', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->setPrimaryKey(['owncloud_name']);
			$table->addUniqueIndex(['ldap_dn_hash'], 'ldap_group_dn_hashes');
			$table->addUniqueIndex(['directory_uuid'], 'ldap_group_directory_uuid');
		}

		if (!$schema->hasTable('ldap_group_members')) {
			$table = $schema->createTable('ldap_group_members');
			$table->addColumn('owncloudname', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('owncloudusers', Types::TEXT, [
				'notnull' => true,
			]);
			$table->setPrimaryKey(['owncloudname']);
		}
		return $schema;
	}
}
