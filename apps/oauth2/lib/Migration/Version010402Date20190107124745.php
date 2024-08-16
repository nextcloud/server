<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version010402Date20190107124745 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		// During an ownCloud migration, the client_identifier column identifier might not exist yet.
		if ($schema->getTable('oauth2_clients')->hasColumn('client_identifier')) {
			$table = $schema->getTable('oauth2_clients');
			$table->dropIndex('oauth2_client_id_idx');
			$table->addUniqueIndex(['client_identifier'], 'oauth2_client_id_idx');
			return $schema;
		}
	}
}
