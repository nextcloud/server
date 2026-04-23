<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\OAuth2\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version012300Date20250423141506 extends SimpleMigrationStep {

	public function __construct(
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('oauth2_access_tokens')) {
			$table = $schema->getTable('oauth2_access_tokens');
			$dbChanged = false;
			if (!$table->hasColumn('hashed_code_challenge')) {
				$table->addColumn('hashed_code_challenge', Types::STRING, [
					'notnull' => false,
					'length' => 128,
				]);
				$dbChanged = true;
			}
			if (!$table->hasColumn('code_challenge_method')) {
				$table->addColumn('code_challenge_method', Types::STRING, [
					'notnull' => false,
					'length' => 10,
				]);
				$dbChanged = true;
			}
			if ($dbChanged) {
				return $schema;
			}
		}

		return null;
	}
}
