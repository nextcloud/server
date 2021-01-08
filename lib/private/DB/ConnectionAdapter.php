<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OC\DB;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Adapts the public API to our internal DBAL connection wrapper
 */
class ConnectionAdapter implements IDBConnection {

	/** @var Connection */
	private $inner;

	public function __construct(Connection $inner) {
		$this->inner = $inner;
	}

	public function getQueryBuilder(): IQueryBuilder {
		return $this->inner->getQueryBuilder();
	}

	public function prepare($sql, $limit = null, $offset = null): IPreparedStatement {
		return new PreparedStatement(
			$this->inner->prepare($sql, $limit, $offset)
		);
	}

	public function executeQuery(string $sql, array $params = [], $types = []): IResult {
		return new ResultAdapter(
			$this->inner->executeQuery($sql, $params, $types)
		);
	}

	public function executeUpdate(string $sql, array $params = [], array $types = []): int {
		return $this->inner->executeUpdate($sql, $params, $types);
	}

	public function executeStatement($sql, array $params = [], array $types = []): int {
		return $this->inner->executeStatement($sql, $params, $types);
	}

	public function lastInsertId(string $table): int {
		return (int) $this->inner->lastInsertId($table);
	}

	public function insertIfNotExist(string $table, array $input, array $compare = null) {
		return $this->inner->insertIfNotExist($table, $input, $compare);
	}

	public function insertIgnoreConflict(string $table, array $values): int {
		return $this->inner->insertIgnoreConflict($table, $values);
	}

	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int {
		return $this->inner->setValues($table, $keys, $values, $updatePreconditionValues);
	}

	public function lockTable($tableName): void {
		$this->inner->lockTable($tableName);
	}

	public function unlockTable(): void {
		$this->inner->unlockTable();
	}

	public function beginTransaction(): void {
		$this->inner->beginTransaction();
	}

	public function inTransaction(): bool {
		return $this->inner->inTransaction();
	}

	public function commit(): void {
		$this->inner->commit();
	}

	public function rollBack(): void {
		$this->inner->rollBack();
	}

	public function getError(): string {
		return $this->inner->getError();
	}

	public function errorCode() {
		return $this->inner->errorCode();
	}

	public function errorInfo() {
		return $this->inner->errorInfo();
	}

	public function connect(): bool {
		return $this->inner->connect();
	}

	public function close(): void {
		$this->inner->close();
	}

	public function quote($input, $type = IQueryBuilder::PARAM_STR) {
		return $this->inner->quote($input, $type);
	}

	/**
	 * @todo we are leaking a 3rdparty type here
	 */
	public function getDatabasePlatform(): AbstractPlatform {
		return $this->inner->getDatabasePlatform();
	}

	public function dropTable(string $table): void {
		$this->inner->dropTable($table);
	}

	public function tableExists(string $table): bool {
		return $this->inner->tableExists($table);
	}

	public function escapeLikeParameter(string $param): string {
		return $this->inner->escapeLikeParameter($param);
	}

	public function supports4ByteText(): bool {
		return $this->inner->supports4ByteText();
	}

	/**
	 * @todo leaks a 3rdparty type
	 */
	public function createSchema(): Schema {
		return $this->inner->createSchema();
	}

	public function migrateToSchema(Schema $toSchema): void {
		$this->inner->migrateToSchema($toSchema);
	}

	public function getInner(): Connection {
		return $this->inner;
	}
}
