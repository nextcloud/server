<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Sharing\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

final class Version1000Date20260826115938 extends SimpleMigrationStep {
	/**
	 * @param Closure():ISchemaWrapper $schemaClosure
	 * @throws SchemaException
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$recipientsTable = $schema->getTable('sharing_share_recipients');
		if (!$recipientsTable->hasColumn('id')) {
			$recipientsTable->addColumn('id', Types::BIGINT);
			$recipientsTable->dropPrimaryKey();
			$recipientsTable->setPrimaryKey(['id']);
			$recipientsTable->addUniqueIndex(['share_id', 'recipient_class_id', 'recipient_value']);
		}

		return $schema;
	}
}
