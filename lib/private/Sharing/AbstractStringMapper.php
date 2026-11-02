<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Sharing;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

abstract class AbstractStringMapper {
	/** @var array<int, string> $map */
	private array $map = [];

	/** @var array<string, int> */
	private array $reverseMap = [];

	private bool $loaded = false;

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly string $mappingTable,
		private readonly string $stringColumn,
		private readonly string $idColumn,
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function insertRow(array $row): void {
		$id = (int)$row[$this->idColumn];
		$value = (string)$row[$this->stringColumn];
		$this->map[$id] = $value;
		$this->reverseMap[$value] = $id;
	}

	private function loadFromDb(): void {
		if ($this->loaded) {
			return;
		}

		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns($this->idColumn, $this->stringColumn)
			->from($this->mappingTable);
		$rows = $query->executeQuery()->fetchAll();

		foreach ($rows as $row) {
			$this->insertRow($row);
		}

		$this->loaded = true;
	}

	private function loadFromDbByName(string $value): ?int {
		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns($this->idColumn, $this->stringColumn)
			->from($this->mappingTable)
			->where($query->expr()->eq($this->stringColumn, $query->createNamedParameter($value)));
		$row = $query->executeQuery()->fetchAssociative();

		if ($row !== false) {
			$this->insertRow($row);
			return (int)$row[$this->idColumn];
		}

		return null;
	}

	private function loadFromDbById(int $id): ?string {
		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns($this->idColumn, $this->stringColumn)
			->from($this->mappingTable)
			->where($query->expr()->eq($this->idColumn, $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$row = $query->executeQuery()->fetchAssociative();

		if ($row !== false) {
			$this->insertRow($row);
			return (string)$row[$this->stringColumn];
		}

		return null;
	}

	private function insert(string $value): int {
		$id = $this->loadFromDbByName($value);
		if ($id !== null) {
			return $id;
		}

		$query = $this->connection->getTypedQueryBuilder();
		$query->insert($this->mappingTable)
			->values([
				$this->stringColumn => $query->createNamedParameter($value)
			]);
		try {
			$query->executeStatement();
			$id = $query->getLastInsertId();
			$this->map[$id] = $value;
			$this->reverseMap[$value] = $id;
			return $id;
		} catch (Exception $exception) {
			// handle concurrent inserts
			if ($exception->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$id = $this->loadFromDbByName($value);
				if ($id === null) {
					throw new \Exception(sprintf("Failed to insert '%s' into sharing_classmap, duplicate on insert but can't fetch it either", $value), $exception->getCode(), $exception);
				}

				return $id;
			}

			throw $exception;
		}
	}

	protected function getId(string $value): int {
		$this->loadFromDb();

		return $this->reverseMap[$value] ?? $this->insert($value);
	}

	protected function getValue(int $id): string {
		$this->loadFromDb();
		if (isset($this->map[$id])) {
			return $this->map[$id];
		}

		$value = $this->loadFromDbById($id);
		if ($value !== null) {
			return $value;
		}

		throw new \Exception(sprintf("Unknown mapped class '%d'", $id));
	}

	public function flush(): void {
		$this->loaded = false;
		$this->map = [];
		$this->reverseMap = [];
	}
}
