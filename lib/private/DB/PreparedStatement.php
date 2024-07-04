<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Statement;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use PDO;

/**
 * Adapts our public API to what doctrine/dbal exposed with 2.6
 *
 * The old dbal statement had stateful methods e.g. to fetch data from an executed
 * prepared statement. To provide backwards compatibility to apps we need to make
 * this class stateful. As soon as those now deprecated exposed methods are gone,
 * we can limit the API of this adapter to the methods that map to the direct dbal
 * methods without much magic.
 */
class PreparedStatement implements IPreparedStatement {
	use TDoctrineParameterTypeMap;

	/** @var Statement */
	private $statement;

	/** @var IResult|null */
	private $result;

	public function __construct(Statement $statement) {
		$this->statement = $statement;
	}

	public function closeCursor(): bool {
		$this->getResult()->closeCursor();

		return true;
	}

	public function fetch(int $fetchMode = PDO::FETCH_ASSOC) {
		return $this->getResult()->fetch($fetchMode);
	}

	public function fetchAll(int $fetchMode = PDO::FETCH_ASSOC): array {
		return $this->getResult()->fetchAll($fetchMode);
	}

	public function fetchColumn() {
		return $this->getResult()->fetchOne();
	}

	public function fetchOne() {
		return $this->getResult()->fetchOne();
	}

	public function bindValue($param, $value, $type = IQueryBuilder::PARAM_STR): bool {
		$this->statement->bindValue($param, $value, $this->convertParameterTypeToDoctrine($type));
		return true;
	}

	public function bindParam($param, &$variable, $type = IQueryBuilder::PARAM_STR, $length = null): bool {
		if ($type !== IQueryBuilder::PARAM_STR) {
			\OC::$server->getLogger()->warning('PreparedStatement::bindParam() is no longer supported. Use bindValue() instead.', ['exception' => new \BadMethodCallException('bindParam() is no longer supported')]);
		}
		$this->bindValue($param, $variable, $type);
		return true;
	}

	public function execute($params = null): IResult {
		if ($params !== null) {
			foreach ($params as $key => $param) {
				if (is_int($key)) {
					// Parameter count starts with 1
					$this->bindValue($key + 1, $param);
				} else {
					$this->bindValue($key, $param);
				}
			}
		}
		return ($this->result = new ResultAdapter($this->statement->executeQuery()));
	}

	public function rowCount(): int {
		return $this->getResult()->rowCount();
	}

	private function getResult(): IResult {
		if ($this->result !== null) {
			return $this->result;
		}

		throw new \Exception("You have to execute the prepared statement before accessing the results");
	}
}
