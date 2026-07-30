<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

final class ConnectionActivityStatement extends AbstractStatementMiddleware {
	public function __construct(
		Statement $statement,
		private ConnectionActivityNotifier $notifier,
	) {
		parent::__construct($statement);
	}

	#[\Override]
	public function execute($params = null): Result {
		$result = parent::execute($params);
		$this->notifier->notify();
		return $result;
	}
}
