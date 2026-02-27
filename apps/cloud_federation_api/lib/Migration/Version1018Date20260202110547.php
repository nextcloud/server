<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\DropTable;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

#[DropTable(table: 'federated_invites', columns: ['id', 'user_id', 'recipient_provider', 'recipient_user_id', 'recipient_name', 'recipient_email', 'token', 'accepted', 'created_at', 'expired_at', 'accepted_at'], description: 'Table is moved out of this app.')]
class Version1018Date20260202110547 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table_name = 'federated_invites';

		if ($schema->hasTable($table_name)) {
			$schema->dropTable($table_name);
			return $schema;
		}
		return null;
	}
}
