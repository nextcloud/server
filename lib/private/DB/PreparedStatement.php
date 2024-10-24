<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
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

	public function bindValue($param, $value, $type = ParameterType::STRING): bool {
		return $this->statement->bindValue($param, $value, $type);
	}

	public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): bool {
		return $this->statement->bindParam($param, $variable, $type, $length);
	}

	public function execute($params = null): IResult {
		return ($this->result = new ResultAdapter($this->statement->execute($params)));
	}

	public function rowCount(): int {
		return $this->getResult()->rowCount();
	}

	private function getResult(): IResult {
		if ($this->result !== null) {
			return $this->result;
		}

		throw new Exception('You have to execute the prepared statement before accessing the results');
	}
}
