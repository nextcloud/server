<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version011602Date20230613160650 extends SimpleMigrationStep {

	public function __construct(
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('oauth2_clients')) {
			$table = $schema->getTable('oauth2_clients');
			if ($table->hasColumn('secret')) {
				$column = $table->getColumn('secret');
				// we still change the column length in case Version011601Date20230522143227
				// has run before it was changed to set the length to 512
				$column->setLength(512);
				return $schema;
			}
		}

		return null;
	}
}
