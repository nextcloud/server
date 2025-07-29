<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1016Date202502262004 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table_name = 'federated_invites';

		if (!$schema->hasTable($table_name)) {
			$table = $schema->createTable($table_name);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,

			]);
			// https://saturncloud.io/blog/what-is-the-maximum-length-of-a-url-in-different-browsers/#maximum-url-length-in-different-browsers
			// We use the least common denominator, the minimum length supported by browsers
			$table->addColumn('recipient_provider', Types::STRING, [
				'notnull' => false,
				'length' => 2083,
			]);
			$table->addColumn('recipient_user_id', Types::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			$table->addColumn('recipient_name', Types::STRING, [
				'notnull' => false,
				'length' => 1024,
			]);
			// https://www.directedignorance.com/blog/maximum-length-of-email-address
			$table->addColumn('recipient_email', Types::STRING, [
				'notnull' => false,
				'length' => 320,
			]);
			$table->addColumn('token', Types::STRING, [
				'notnull' => true,
				'length' => 60,
			]);
			$table->addColumn('accepted', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$table->addColumn('created_at', Types::BIGINT, [
				'notnull' => true,
			]);

			$table->addColumn('expired_at', Types::BIGINT, [
				'notnull' => false,
			]);

			$table->addColumn('accepted_at', Types::BIGINT, [
				'notnull' => false,
			]);

			$table->addUniqueConstraint(['token']);
			$table->setPrimaryKey(['id']);
			return $schema;
		}

		return null;
	}
}
