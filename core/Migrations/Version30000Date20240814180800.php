<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Migrations;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version30000Date20240814180800 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('webauthn');
		$column = $table->getColumn('public_key_credential_id');

		/**
		 * There is no maximum length defined in the standard,
		 * most common the length is between 128 and 200 characters,
		 * but as we store it not in plain data but base64 encoded the length can grow about 1/3.
		 * We had a regression with 'Nitrokey 3' which created IDs with 196 byte length -> 262 bytes encoded base64.
		 * So to be save we increase the size to 512 bytes.
		 */
		if ($column->getLength() < 512) {
			$column->setLength(512);
		}

		return $schema;
	}
}
