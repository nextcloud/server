<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\DB;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Schema;
use OC\DB\Exceptions\DbalException;
use OCP\DB\IPreparedStatement;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Adapts the public API to our internal DBAL connection wrapper
 */
class ConnectionAdapter implements IDBConnection {
	public function __construct(
		protected Connection $inner,
	) {
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
			throw DbalException::wrap($e);
		}
	}

	public function executeUpdate(string $sql, array $params = [], array $types = []): int {
		try {
			return $this->inner->executeUpdate($sql, $params, $types);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function executeStatement($sql, array $params = [], array $types = []): int {
		try {
			return $this->inner->executeStatement($sql, $params, $types);
		} catch (Exception $e) {
			throw DbalException::wrap($e);
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
			$this->inner->connect();
			return true;
		} catch (Exception $e) {
			throw DbalException::wrap($e);
		}
	}

	public function close(): void {
		$this->inner->close();
	}

	/**
	 * @param mixed $input
	 * @param int $type
	 * @deprecated 30.0.0 Only strings are supported as database type in the end and the $type parameter is ignored going forward
	 */
	public function quote($input, $type = IQueryBuilder::PARAM_STR) {
		if ($type !== IQueryBuilder::PARAM_STR) {
			\OC::$server->getLogger()->debug('Parameter $type is no longer supported and the function only handles resulting database type string', ['exception' => new \InvalidArgumentException('$type parameter is no longer supported')]);
		}
		return $this->inner->getDatabasePlatform()->quoteStringLiteral($input);
	}

	/**
	 * @todo we are leaking a 3rdparty type here
	 * @deprecated 30.0.0 Use {@see getDatabaseProvider()} instead
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

	public function getDatabaseProvider(): string {
		$platform = $this->inner->getDatabasePlatform();
		if ($platform instanceof MySQLPlatform) {
			return IDBConnection::PLATFORM_MYSQL;
		} elseif ($platform instanceof OraclePlatform) {
			return IDBConnection::PLATFORM_ORACLE;
		} elseif ($platform instanceof PostgreSQLPlatform) {
			return IDBConnection::PLATFORM_POSTGRES;
		} elseif ($platform instanceof SQLitePlatform) {
			return IDBConnection::PLATFORM_SQLITE;
		} else {
			throw new \Exception('Database ' . $platform::class . ' not supported');
		}
	}
}
