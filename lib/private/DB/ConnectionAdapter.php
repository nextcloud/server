<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use OC\DB\Exceptions\DbalException;
use OC\DB\QueryBuilder\Sharded\CrossShardMoveHelper;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
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
		try {
			return new PreparedStatement(
				$this->inner->prepare($sql, $limit, $offset)
			);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function executeQuery(string $sql, array $params = [], $types = []): IResult {
		try {
			return new ResultAdapter(
				$this->inner->executeQuery($sql, $params, $types)
			);
		} catch (Exception $e) {
			throw DbalException::wrap($e, '', $sql);
		}
	}

	public function executeUpdate(string $sql, array $params = [], array $types = []): int {
		try {
			return $this->inner->executeUpdate($sql, $params, $types);
		} catch (Exception $e) {
			throw DbalException::wrap($e, '', $sql);
		}
	}

	public function executeStatement($sql, array $params = [], array $types = []): int {
		try {
			return $this->inner->executeStatement($sql, $params, $types);
		} catch (Exception $e) {
			throw DbalException::wrap($e, '', $sql);
		}
	}

	public function lastInsertId(string $table): int {
		try {
			return $this->inner->lastInsertId($table);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function insertIfNotExist(string $table, array $input, ?array $compare = null) {
		try {
			return $this->inner->insertIfNotExist($table, $input, $compare);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function insertIgnoreConflict(string $table, array $values): int {
		try {
			return $this->inner->insertIgnoreConflict($table, $values);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function setValues($table, array $keys, array $values, array $updatePreconditionValues = []): int {
		try {
			return $this->inner->setValues($table, $keys, $values, $updatePreconditionValues);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function lockTable($tableName): void {
		try {
			$this->inner->lockTable($tableName);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function unlockTable(): void {
		try {
			$this->inner->unlockTable();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function beginTransaction(): void {
		try {
			$this->inner->beginTransaction();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function inTransaction(): bool {
		return $this->inner->inTransaction();
	}

	public function commit(): void {
		try {
			$this->inner->commit();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function rollBack(): void {
		try {
			$this->inner->rollBack();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
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
		try {
			return $this->inner->connect();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
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
		try {
			$this->inner->dropTable($table);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function truncateTable(string $table, bool $cascade): void {
		try {
			$this->inner->truncateTable($table, $cascade);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function tableExists(string $table): bool {
		try {
			return $this->inner->tableExists($table);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
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
		try {
			return $this->inner->createSchema();
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function migrateToSchema(Schema $toSchema): void {
		try {
			$this->inner->migrateToSchema($toSchema);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function getInner(): Connection {
		return $this->inner;
	}

	/**
	 * @return self::PLATFORM_MYSQL|self::PLATFORM_ORACLE|self::PLATFORM_POSTGRES|self::PLATFORM_SQLITE|self::PLATFORM_MARIADB
	 */
	public function getDatabaseProvider(bool $strict = false): string {
		return $this->inner->getDatabaseProvider($strict);
	}

	/**
	 * @internal Should only be used inside the QueryBuilder, ExpressionBuilder and FunctionBuilder
	 * All apps and API code should not need this and instead use provided functionality from the above.
	 */
	public function getServerVersion(): string {
		return $this->inner->getServerVersion();
	}

	public function logDatabaseException(\Exception $exception) {
		$this->inner->logDatabaseException($exception);
	}

	public function getShardDefinition(string $name): ?ShardDefinition {
		return $this->inner->getShardDefinition($name);
	}

	public function getCrossShardMoveHelper(): CrossShardMoveHelper {
		return $this->inner->getCrossShardMoveHelper();
	}
}
