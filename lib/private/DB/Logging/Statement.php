<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2024 Doctrine Project
 * SPDX-License-Identifier: MIT
 */

namespace OC\DB\Logging;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use OC\DB\TDoctrineParameterTypeMap;
use OCP\Diagnostics\IQueryLogger;

final class Statement extends AbstractStatementMiddleware {
	use TDoctrineParameterTypeMap;

	/** @var array<int,mixed>|array<string,mixed> */
	private array $params = [];

	/** @var array<int,string>|array<string,string> */
	private array $types = [];

	public function __construct(
		StatementInterface $statement,
		private readonly IQueryLogger $queryLogger,
		private readonly string $sql,
	) {
		parent::__construct($statement);
	}

	public function bindValue(int|string $param, mixed $value, ParameterType $type): void {
		$this->params[$param] = $value;
		$this->types[$param] = $this->convertParameterTypeToJsonSerializable($type);

		parent::bindValue($param, $value, $type);
	}

	public function execute(): ResultInterface {
		$this->queryLogger->startQuery($this->sql, $this->params, $this->types);
		$result = parent::execute();
		$this->queryLogger->stopQuery();

		return $result;
	}
}
