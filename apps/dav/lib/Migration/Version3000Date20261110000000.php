<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Subscriptions default to transparent so existing subscriptions keep
 * not affecting the subscriber's free/busy information.
 */
#[AddColumn(table: 'calendarsubscriptions', name: 'transparent', type: ColumnType::SMALLINT)]
class Version3000Date20261110000000 extends SimpleMigrationStep {
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('calendarsubscriptions');
		if ($table->hasColumn('transparent')) {
			return null;
		}

		$table->addColumn('transparent', Types::SMALLINT, [
			'notnull' => true,
			'length' => 1,
			'default' => 1,
		]);

		return $schema;
	}
}
