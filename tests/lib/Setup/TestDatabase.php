<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Setup;

use OC\DB\Connection;
use OC\DB\ConnectionFactory;
use OC\Setup\AbstractDatabase;

/**
 * Minimal concrete implementation to test the shared setup logic of
 * {@see AbstractDatabase}, with the connection factory made injectable.
 */
class TestDatabase extends AbstractDatabase {
	public string $dbprettyname = 'Test';

	public ConnectionFactory $connectionFactory;

	#[\Override]
	protected function createConnectionFactory(): ConnectionFactory {
		return $this->connectionFactory;
	}

	#[\Override]
	public function setupDatabase(): void {
	}

	public function connectForTest(array $configOverwrite = []): Connection {
		return $this->connect($configOverwrite);
	}
}
