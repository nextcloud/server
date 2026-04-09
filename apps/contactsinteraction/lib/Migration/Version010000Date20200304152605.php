<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Migration;

use Closure;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010000Date20200304152605 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->createTable(RecentContactMapper::TABLE_NAME);
		$table->addColumn('id', 'integer', [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 4,
		]);
		$table->addColumn('actor_uid', 'string', [
			'notnull' => true,
			'length' => 64,
		]);
		$table->addColumn('uid', 'string', [
			'notnull' => false,
			'length' => 64,
		]);
		$table->addColumn('email', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('federated_cloud_id', 'string', [
			'notnull' => false,
			'length' => 255,
		]);
		$table->addColumn('card', 'blob', [
			'notnull' => true,
		]);
		$table->addColumn('last_contact', 'integer', [
			'notnull' => true,
			'length' => 4,
		]);
		$table->setPrimaryKey(['id']);
		// To find all recent entries
		$table->addIndex(['actor_uid'], RecentContactMapper::TABLE_NAME . '_actor_uid');
		// To find a specific entry
		$table->addIndex(['id', 'actor_uid'], RecentContactMapper::TABLE_NAME . '_id_uid');
		// To find all recent entries with a given UID
		$table->addIndex(['uid'], RecentContactMapper::TABLE_NAME . '_uid');
		// To find all recent entries with a given email address
		$table->addIndex(['email'], RecentContactMapper::TABLE_NAME . '_email');
		// To find all recent entries with a give federated cloud id
		$table->addIndex(['federated_cloud_id'], RecentContactMapper::TABLE_NAME . '_fed_id');
		// For the cleanup
		$table->addIndex(['last_contact'], RecentContactMapper::TABLE_NAME . '_last_contact');

		return $schema;
	}
}
