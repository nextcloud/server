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

final class ClassMapper {
	/** @var array<int, class-string> $map */
	private array $map = [];

	/** @var array<class-string, int> */
	private array $reverseMap = [];

	private bool $loaded = false;

	public function __construct(
		private readonly IDBConnection $connection,
	) {
	}

	/**
	 * @param array{class_id: int|string, class_name: class-string} $row
	 */
	private function insertRow(array $row): void {
		$id = (int)$row['class_id'];
		$class = $row['class_name'];
		$this->map[$id] = $class;
		$this->reverseMap[$class] = $id;
	}

	private function loadFromDb(): void {
		if ($this->loaded) {
			return;
		}

		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns('class_id', 'class_name')
			->from('sharing_classmap');
		$rows = $query->executeQuery()->fetchAll();

		foreach ($rows as $row) {
			/** @var array{class_id: int|string, class_name: class-string} $row */
			$this->insertRow($row);
		}

		$this->loaded = true;
	}

	/**
	 * @param class-string $className
	 */
	private function loadFromDbByName(string $className): ?int {
		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns('class_id', 'class_name')
			->from('sharing_classmap')
			->where($query->expr()->eq('class_name', $query->createNamedParameter($className)));
		$row = $query->executeQuery()->fetchAssociative();

		if ($row !== false) {
			/** @var array{class_id: int|string, class_name: class-string} $row */
			$this->insertRow($row);
			return (int)$row['class_id'];
		}

		return null;
	}

	/**
	 * @return class-string|null
	 */
	private function loadFromDbById(int $id): ?string {
		$query = $this->connection->getTypedQueryBuilder();
		$query->selectColumns('class_id', 'class_name')
			->from('sharing_classmap')
			->where($query->expr()->eq('class_id', $query->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		$row = $query->executeQuery()->fetchAssociative();

		if ($row !== false) {
			/** @var array{class_id: int|string, class_name: class-string} $row */
			$this->insertRow($row);
			return $row['class_name'];
		}

		return null;
	}

	/**
	 * @param class-string $className
	 */
	private function insert(string $className): int {
		$id = $this->loadFromDbByName($className);
		if ($id !== null) {
			return $id;
		}

		$query = $this->connection->getTypedQueryBuilder();
		$query->insert('sharing_classmap')
			->values([
				'class_name' => $query->createNamedParameter($className)
			]);
		try {
			$query->executeStatement();
			$id = $query->getLastInsertId();
			$this->map[$id] = $className;
			$this->reverseMap[$className] = $id;
			return $id;
		} catch (Exception $exception) {
			// handle concurrent inserts
			if ($exception->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				$id = $this->loadFromDbByName($className);
				if ($id === null) {
					throw new \Exception(sprintf("Failed to insert '%s' into sharing_classmap, duplicate on insert but can't fetch it either", $className), $exception->getCode(), $exception);
				}

				return $id;
			}

			throw $exception;
		}
	}

	/**
	 * @param class-string $class
	 */
	public function getClassId(string $class): int {
		$this->loadFromDb();

		return $this->reverseMap[$class] ?? $this->insert($class);
	}

	/**
	 * @return class-string
	 */
	public function getClassName(int $id): string {
		$this->loadFromDb();
		if (isset($this->map[$id])) {
			return $this->map[$id];
		}

		$class = $this->loadFromDbById($id);
		if ($class) {
			return $class;
		}

		throw new \Exception(sprintf("Unknown mapped class '%d'", $id));
	}

	public function flush(): void {
		$this->loaded = false;
		$this->map = [];
		$this->reverseMap = [];
	}
}
