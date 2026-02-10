<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCA\Files\Db\ResumableUploadMapper;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2003Date20241126094807 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable(ResumableUploadMapper::TABLE_NAME)) {
			$table = $schema->createTable(ResumableUploadMapper::TABLE_NAME);
			$table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 11,
				'unsigned' => true,
			]);
			$table->addColumn('user_id', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('token', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('path', Types::TEXT, [
				'notnull' => true,
			]);
			$table->addColumn('size', Types::BIGINT, [
				'notnull' => false,
			]);
			$table->addColumn('complete', Types::BOOLEAN, [
				'notnull' => false,
			]);
			$table->addUniqueIndex(['token'], ResumableUploadMapper::TABLE_NAME . '_token_idx');
			$table->addIndex(['user_id', 'token'], ResumableUploadMapper::TABLE_NAME . '_uid_token_idx');
			$table->setPrimaryKey(['id']);

			return $schema;
		}

		return null;
	}
}
