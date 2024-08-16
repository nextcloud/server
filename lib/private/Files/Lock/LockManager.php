<?php

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\Lock;

use OCP\Files\Lock\ILock;
use OCP\Files\Lock\ILockManager;
use OCP\Files\Lock\ILockProvider;
use OCP\Files\Lock\LockContext;
use OCP\PreConditionNotMetException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class LockManager implements ILockManager {
	private ?string $lockProviderClass = null;
	private ?ILockProvider $lockProvider = null;
	private ?LockContext $lockInScope = null;

	public function registerLockProvider(ILockProvider $lockProvider): void {
		if ($this->lockProvider) {
			throw new PreConditionNotMetException('There is already a registered lock provider');
		}

		$this->lockProvider = $lockProvider;
	}

	public function registerLazyLockProvider(string $lockProviderClass): void {
		if ($this->lockProviderClass || $this->lockProvider) {
			throw new PreConditionNotMetException('There is already a registered lock provider');
		}

		$this->lockProviderClass = $lockProviderClass;
	}

	private function getLockProvider(): ?ILockProvider {
		if ($this->lockProvider) {
			return $this->lockProvider;
		}
		if ($this->lockProviderClass) {
			try {
				$this->lockProvider = \OCP\Server::get($this->lockProviderClass);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
			}
		}

		return $this->lockProvider;
	}

	public function isLockProviderAvailable(): bool {
		return $this->getLockProvider() !== null;
	}

	public function runInScope(LockContext $lock, callable $callback): void {
		if (!$this->getLockProvider()) {
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
		if (!$this->getLockProvider()) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		return $this->getLockProvider()->getLocks($fileId);
	}

	public function lock(LockContext $lockInfo): ILock {
		if (!$this->getLockProvider()) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		return $this->getLockProvider()->lock($lockInfo);
	}

	public function unlock(LockContext $lockInfo): void {
		if (!$this->getLockProvider()) {
			throw new PreConditionNotMetException('No lock provider available');
		}

		$this->getLockProvider()->unlock($lockInfo);
	}
}
