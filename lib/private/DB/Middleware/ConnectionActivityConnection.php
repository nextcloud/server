<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\PDO\Connection as PDOConnection;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class ConnectionActivityConnection extends AbstractConnectionMiddleware {
	public function __construct(
		private Connection $inner,
		private ConnectionActivityNotifier $notifier,
	) {
		parent::__construct($inner);
	}

	/**
	 * Kept working for consumers that reach the native PDO handle through the
	 * deprecated accessor, like SQLiteSessionInit: forwarding is intentionally
	 * preferred over migrating the callers, as those code paths get refactored
	 * with the DBAL 4 upgrade anyway.
	 */
	public function getWrappedConnection(): \PDO {
		if (!$this->inner instanceof PDOConnection) {
			throw new \LogicException('The wrapped connection is not a PDO based connection');
		}
		return $this->inner->getWrappedConnection();
	}

	#[\Override]
	public function prepare(string $sql): Statement {
		return new ConnectionActivityStatement(parent::prepare($sql), $this->notifier);
	}

	#[\Override]
	public function query(string $sql): Result {
		$result = parent::query($sql);
		$this->notifier->notify();
		return $result;
	}

	#[\Override]
	public function exec(string $sql): int {
		$result = parent::exec($sql);
		$this->notifier->notify();
		return $result;
	}
}
