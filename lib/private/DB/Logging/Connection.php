<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2024 Doctrine Project
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Logging;

use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use OCP\Diagnostics\IQueryLogger;

final class Connection extends AbstractConnectionMiddleware {
	public function __construct(
		ConnectionInterface $connection,
		private readonly IQueryLogger $queryLogger,
	) {
		parent::__construct($connection);
	}

	public function prepare(string $sql): DriverStatement {
		return new Statement(
			parent::prepare($sql),
			$this->queryLogger,
			$sql,
		);
	}

	public function query(string $sql): Result {
		$this->queryLogger->startQuery($sql);
		$result = parent::query($sql);
		$this->queryLogger->stopQuery();

		return $result;
	}

	public function exec(string $sql): int|string {
		$this->queryLogger->startQuery($sql);
		$result = parent::exec($sql);
		$this->queryLogger->stopQuery();

		return $result;
	}
}
