<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Repair;

use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class TruncateMounts implements IRepairStep {
	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	public function getName(): string {
		return 'Deduplicate mounts';
	}

	public function run(IOutput $output): void {
		$this->connection->truncateTable('mounts', false);
	}
}
