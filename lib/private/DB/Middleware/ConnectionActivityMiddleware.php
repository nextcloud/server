<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Doctrine middleware reporting every query and statement execution back to
 * the owning connection, so the idle timer of the connectivity check can be
 * refreshed (see \OC\DB\Connection::refreshLastConnectionCheck()). Working on
 * the driver level covers executions of prepared statements as well, which
 * bypass the executeQuery() and executeStatement() methods of the connection.
 */
final class ConnectionActivityMiddleware implements Middleware {
	private ConnectionActivityNotifier $notifier;

	public function __construct() {
		$this->notifier = new ConnectionActivityNotifier();
	}

	/**
	 * Hand the notifier to the connection that wants to listen, e.g. via the
	 * connection parameters.
	 */
	public function getNotifier(): ConnectionActivityNotifier {
		return $this->notifier;
	}

	#[\Override]
	public function wrap(Driver $driver): Driver {
		return new ConnectionActivityDriver($driver, $this->notifier);
	}
}
