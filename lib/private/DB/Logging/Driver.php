<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2024 Doctrine Project
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Logging;

use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use OCP\Diagnostics\IQueryLogger;
use SensitiveParameter;

final class Driver extends AbstractDriverMiddleware {
	public function __construct(
		DriverInterface $driver,
		private readonly IQueryLogger $queryLogger,
	) {
		parent::__construct($driver);
	}

	public function connect(
		#[SensitiveParameter]
		array $params,
	): Connection {
		return new Connection(
			parent::connect($params),
			$this->queryLogger,
		);
	}
}
