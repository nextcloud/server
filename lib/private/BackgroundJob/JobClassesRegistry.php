<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\BackgroundJob;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use InvalidArgumentException;
use OCP\BackgroundJob\IJob;
use OCP\IDBConnection;
use OCP\Snowflake\ISnowflakeGenerator;

/**
 * Map background job classes and their ID in database
 */
final class JobClassesRegistry {
	/**
	 * @var array<string,string>
	 */
	private array $registry = [];

	private const TABLE = 'job_classes_registry';

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly ISnowflakeGenerator $snowflakeGenerator,
	) {
	}

	private function loadRegistry(): void {
		if ($this->registry !== []) {
			return;
		}
		$qb = $this->connection->getQueryBuilder();
		$result = $qb->select('class_id', 'class_name')->from(self::TABLE)->executeQuery();
		foreach ($result->iterateAssociative() as $row) {
			$this->registry[$row['class_name']] = (string)$row['class_id'];
		}
	}

	/**
	 * Resolve current ID or generates a new one
	 */
	public function getId(string $className): string {
		$this->loadRegistry();
		if (isset($this->registry[$className])) {
			return $this->registry[$className];
		}

		if (!class_exists($className)) {
			throw new InvalidArgumentException('Class ' . $className . ' doesn’t exists');
		}
		if (!is_a($className, IJob::class, true)) {
			throw new InvalidArgumentException('Class ' . $className . ' isn’t an instance of ' . IJob::class);
		}

		$qb = $this->connection->getQueryBuilder();
		try {
			$classId = $this->snowflakeGenerator->nextId();
			$qb
				->insert(self::TABLE)
				->values([
					'class_id' => $qb->createNamedParameter($classId),
					'class_name' => $qb->createNamedParameter($className),
				])
				->executeStatement();
			$this->registry[$className] = $classId;

			return $classId;
		} catch (UniqueConstraintViolationException $e) {
			// Class was probably added by a concurrent process
			// Try to load it
			$result = $qb->select('class_id')->from(self::TABLE)->where($qb->expr()->eq('class_name', $className))->executeQuery();
			if ($classId = $result->fetchOne()) {
				$classId = (string)$classId;
				$this->registry[$className] = $classId;

				return $classId;
			}
		}

		throw new \Exception('Fail to retrieve ' . $className . ' ID', previous: $e);
	}

	public function getName(string|int $classId): string {
		$this->loadRegistry();
		$classId = (string)$classId;
		$className = array_search($classId, $this->registry, true);
		if ($className === false) {
			throw new InvalidArgumentException('Class ID ' . $classId . ' doesn’t match any class name');
		}

		return $className;
	}
}
