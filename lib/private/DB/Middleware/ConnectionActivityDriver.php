<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\DB\Middleware;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

final class ConnectionActivityDriver extends AbstractDriverMiddleware {
	public function __construct(
		Driver $driver,
		private ConnectionActivityNotifier $notifier,
	) {
		parent::__construct($driver);
	}

	#[\Override]
	public function connect(array $params) {
		return new ConnectionActivityConnection(parent::connect($params), $this->notifier);
	}
}
