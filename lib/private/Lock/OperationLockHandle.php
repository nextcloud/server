<?php

declare(strict_types=1);

namespace OC\Lock;

use OCP\Lock\ILockingProvider;

/**
 * Represents operation locks acquired by the current request.
 *
 * @internal
 */
final class OperationLockHandle {
	private bool $released = false;

	/**
	 * @param list<string> $providerKeys Keys acquired in deterministic order.
	 */
	public function __construct(
		private readonly ILockingProvider $lockingProvider,
		private readonly array $providerKeys,
	) {
	}

	public function release(): void {
		if ($this->released) {
			return;
		}

		$this->released = true;

		foreach (array_reverse($this->providerKeys) as $key) {
			$this->lockingProvider->releaseLock(
				$key,
				ILockingProvider::LOCK_EXCLUSIVE,
			);
		}
	}
}
