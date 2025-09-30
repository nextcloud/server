<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);


namespace OCA\Sharing\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

class Version1000Date20250929161325 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$table = $schema->createTable('sharing_share');
		$table->addColumn('id', Types::STRING, ['length' => 36]);
		$table->addColumn('creator', Types::TEXT);
		$table->addColumn('source_type', Types::TEXT);
		$table->addColumn('recipient_type', Types::TEXT);

		$table = $schema->createTable('sharing_share_sources');
		$table->addColumn('share_id', Types::STRING, ['length' => 36]);
		$table->addColumn('source', Types::TEXT);

		$table = $schema->createTable('sharing_share_recipients');
		$table->addColumn('share_id', Types::STRING, ['length' => 36]);
		$table->addColumn('recipient', Types::TEXT);

		$table = $schema->createTable('sharing_share_properties');
		$table->addColumn('share_id', Types::STRING, ['length' => 36]);
		$table->addColumn('feature', Types::TEXT);
		$table->addColumn('key', Types::TEXT);
		$table->addColumn('value', Types::TEXT);

		// TODO: Add primary keys, unique constraints and indices

		return $schema;
	}
}
