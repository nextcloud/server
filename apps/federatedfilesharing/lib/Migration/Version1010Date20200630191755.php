<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1010Date20200630191755 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('federated_reshares')) {
			$table = $schema->createTable('federated_reshares');
			$table->addColumn('share_id', Types::BIGINT, [
				'notnull' => true,
			]);
			$table->addColumn('remote_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
				'default' => '',
			]);
			$table->setPrimaryKey(['share_id'], 'federated_res_pk');
			//			$table->addUniqueIndex(['share_id'], 'share_id_index');
		}
		return $schema;
	}
}
