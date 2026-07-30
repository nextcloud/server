<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCP\IConfig;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Serialize concurrent WebDAV MOVE and COPY on the same source or destination path.
 * Enable via 'dav.serialize_move_copy'.
 */
class SerializeMoveCopyPlugin extends ServerPlugin {
	/** Distinct namespace from the storage-layer View locks on the same path. */
	private const LOCK_KEY_PREFIX = 'webdav-serialize:';

	private const CONFIG_KEY = 'dav.serialize_move_copy';

	/** @var list<array{key: string, type: int}> */
	private array $heldLocks = [];

	public function __construct(
		private ILockingProvider $lockingProvider,
		private IConfig $config,
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$server->on('beforeMove', [$this, 'beforeMove']);
		$server->on('beforeCopy', [$this, 'beforeCopy']);
		$server->on('afterMethod:MOVE', [$this, 'afterMethod']);
		$server->on('afterMethod:COPY', [$this, 'afterMethod']);
		$server->on('exception', [$this, 'onException']);
	}

	/** @throws FileLocked when the source or destination is contended. */
	public function beforeMove(string $source, string $destination): bool {
		return $this->guard($source, $destination, ILockingProvider::LOCK_EXCLUSIVE);
	}

	/** @throws FileLocked when the source or destination is contended. */
	public function beforeCopy(string $source, string $destination): bool {
		return $this->guard($source, $destination, ILockingProvider::LOCK_SHARED);
	}

	private function guard(string $source, string $destination, int $sourceLockType): bool {
		if (!$this->config->getSystemValueBool(self::CONFIG_KEY, false)) {
			return true;
		}
		$srcKey = self::LOCK_KEY_PREFIX . $source;
		$dstKey = self::LOCK_KEY_PREFIX . $destination;
		if ($srcKey === $dstKey) {
			return true;
		}
		// Path sort ensures two concurrent operations with swapped source and destination acquire in the same order.
		$order = strcmp($srcKey, $dstKey) < 0
			? [[$srcKey, $sourceLockType, $source], [$dstKey, ILockingProvider::LOCK_EXCLUSIVE, $destination]]
			: [[$dstKey, ILockingProvider::LOCK_EXCLUSIVE, $destination], [$srcKey, $sourceLockType, $source]];
		try {
			foreach ($order as [$key, $type, $readablePath]) {
				$this->lockingProvider->acquireLock($key, $type, $readablePath);
				$this->heldLocks[] = ['key' => $key, 'type' => $type];
			}
		} catch (LockedException $e) {
			$this->release();
			throw new FileLocked($e->getMessage(), $e->getCode(), $e);
		}
		return true;
	}

	public function afterMethod(RequestInterface $request, ResponseInterface $response): void {
		$this->release();
	}

	public function onException(\Throwable $exception): void {
		// afterMethod does not fire on exception. Release here so locks do not leak.
		$this->release();
	}

	public function __destruct() {
		$this->release();
	}

	private function release(): void {
		while ($lock = array_pop($this->heldLocks)) {
			try {
				$this->lockingProvider->releaseLock($lock['key'], $lock['type']);
			} catch (\Throwable) {
				// Multiple release() calls are expected across the hooks and destructor. Suppress to stay idempotent.
			}
		}
	}
}
