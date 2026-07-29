<?php

declare(strict_types=1);

namespace OC\Lock;

use InvalidArgumentException;
use OCP\Lock\ILockingProvider;

/**
 * Coordinates short-lived internal advisory locks for logical operations.
 *
 * This is separate from:
 * - filesystem transactional locks; and
 * - persistent files_lock collaboration locks.
 *
 * @internal
 */
final class OperationLockManager {
	private const KEY_PREFIX = 'operation-lock:v1:';

	public function __construct(
		private readonly ILockingProvider $lockingProvider,
	) {
	}

	/**
	 * Acquire exclusive advisory locks, invoke the callback, then release all
	 * locks in reverse acquisition order.
	 *
	 * State observed before entering the callback must be revalidated inside it.
	 *
	 * @template T
	 * @param non-empty-string $scope Owning subsystem, e.g. "files" or "encryption".
	 * @param non-empty-string $operation Logical operation, e.g. "transfer".
	 * @param non-empty-list<string> $resources Canonical caller-defined identities.
	 * @param callable(): T $callback
	 * @return T
	 * @throws \OCP\Lock\LockedException
	 */
	public function withLock(
		string $scope,
		string $operation,
		array $resources,
		string $readableOperation,
		callable $callback,
	): mixed {
		$lock = $this->acquire(
			$scope,
			$operation,
			$resources,
			$readableOperation,
		);

		try {
			return $callback();
		} finally {
			$lock->release();
		}
	}

	/**
	 * Lower-level API for an operation that cannot use one lexical scope.
	 *
	 * @param non-empty-string $scope
	 * @param non-empty-string $operation
	 * @param non-empty-list<string> $resources
	 */
	public function acquire(
		string $scope,
		string $operation,
		array $resources,
		string $readableOperation,
	): OperationLockHandle {
		$this->validateSegment($scope);
		$this->validateSegment($operation);

		$providerKeys = array_map(
			fn (string $resource): string => $this->providerKey(
				$scope,
				$operation,
				$resource,
			),
			$resources,
		);

		$providerKeys = array_values(array_unique($providerKeys));
		sort($providerKeys, SORT_STRING);

		$acquired = [];
		try {
			foreach ($providerKeys as $key) {
				$this->lockingProvider->acquireLock(
					$key,
					ILockingProvider::LOCK_EXCLUSIVE,
					$readableOperation,
				);
				$acquired[] = $key;
			}
		} catch (\Throwable $e) {
			foreach (array_reverse($acquired) as $key) {
				$this->lockingProvider->releaseLock(
					$key,
					ILockingProvider::LOCK_EXCLUSIVE,
				);
			}
			throw $e;
		}

		return new OperationLockHandle($this->lockingProvider, $acquired);
	}

	private function providerKey(
		string $scope,
		string $operation,
		string $resource,
	): string {
		return self::KEY_PREFIX
			. $scope
			. ':'
			. $operation
			. ':'
			. hash('sha256', $resource);
	}

	private function validateSegment(string $segment): void {
		if (preg_match('/^[a-z0-9][a-z0-9.-]*$/', $segment) !== 1) {
			throw new InvalidArgumentException(
				'Operation-lock scope and operation must contain lowercase letters, digits, dots, or hyphens.',
			);
		}
	}
}
