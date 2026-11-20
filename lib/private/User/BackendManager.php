<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\User;

use Generator;
use NCU\User\IUserBackend;
use NCU\User\IUserBackendManager;
use OCP\IDBConnection;
use Override;
use RuntimeException;

final readonly class BackendManager implements IUserBackendManager {
	private const TABLE = 'user_backends';

	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[Override]
	public function register(string $name, string $class): int {
		return 0;
	}

	#[Override]
	public function remove(int $backendId): bool {
	}

	#[Override]
	public function list(): Generator {
		$qb = $this->connection->getQueryBuilder();
		$results = $qb->select('backend_id', 'backend_name', 'backend_class')->from(self::TABLE)->executeQuery();
		foreach ($results->iterateAssociative() as $row) {
			yield $this->buildBackend($row['backend_id'], $row['backend_name'], $row['backend_class']);
		}
	}

	#[Override]
	public function get(int $backendId): IUserBackend {
		$qb = $this->connection->getQueryBuilder();
		$results = $qb
			->select('backend_name', 'backend_class')
			->from(self::TABLE)
			->where($qb->expr()->eq('backend_id', $qb->createNamedParameter($backendId)))
			->executeQuery();
		$row = $results->fetchAssociative();
		if ($row === false) {
			throw new RuntimeException('Backend not found');
		}
		return $this->buildBackend($backendId, $row['backend_name'], $row['backend_class']);
	}

	private function buildBackend(int $backendId, $backendName, $backendClass): IUserBackend {
		if (!is_subclass_of($backendClass, IUserBackend::class, true)) {
			throw new RuntimeException('Invalid class registered');
		}

		return new $backendClass($this->connection, $backendId, $backendName);
	}
}
