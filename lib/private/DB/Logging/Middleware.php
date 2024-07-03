<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2024 Doctrine Project
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware as MiddlewareInterface;
use OCP\Diagnostics\IQueryLogger;

final class Middleware implements MiddlewareInterface {
	public function __construct(
		private readonly IQueryLogger $queryLogger,
	) {
	}

	public function wrap(DriverInterface $driver): DriverInterface {
		return new Driver(
			$driver,
			$this->queryLogger,
		);
	}
}
