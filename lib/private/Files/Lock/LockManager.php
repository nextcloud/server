<?php

namespace OC\Files\Lock;

use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\ILockProvider;
use OCP\Files\Lock\LockContext;
use OCP\PreConditionNotMetException;

class LockManager implements ILockManager {
	private ?ILockProvider $lockProvider = null;
	private ?LockContext $lockInScope = null;

	public function registerLockProvider(ILockProvider $lockProvider): void {
		if ($this->lockProvider) {
			throw new PreConditionNotMetException('There is already a registered lock provider');
		}

		$this->lockProvider = $lockProvider;
	}

	public function isLockProviderAvailable(): bool {
		return $this->lockProvider !== null;
	}

	public function runInScope(LockContext $lock, callable $callback): void {
		if (!$this->lockProvider) {
			$callback();
			return;
		}

		if ($this->lockInScope) {
			throw new PreConditionNotMetException('Could not obtain lock scope as already in use by ' . $this->lockInScope);
		}

		try {
			$this->lockInScope = $lock;
			$callback();
		} finally {
			$this->lockInScope = null;
		}
	}

	public function getLockInScope(): ?LockContext {
		return $this->lockInScope;
	}

	public function getLocks(int $fileId): array {
		if (!$this->lockProvider) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		return $this->lockProvider->getLocks($fileId);
	}

	public function lock(LockContext $lockInfo): ILock {
		if (!$this->lockProvider) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		return $this->lockProvider->lock($lockInfo);
	}

	public function unlock(LockContext $lockInfo): void {
		if (!$this->lockProvider) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		$this->lockProvider->unlock($lockInfo);
	}
}
