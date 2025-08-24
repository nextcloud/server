<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

class LockPlugin extends ServerPlugin {
	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * State of the lock
	 *
	 * @var bool
	 */
	private $isLocked;

	/**
	 * {@inheritdoc}
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;
		$this->server->on('beforeMethod:*', [$this, 'getLock'], 50);
		$this->server->on('afterMethod:*', [$this, 'releaseLock'], 50);
		$this->isLocked = false;
	}

	public function getLock(RequestInterface $request) {
		// we can't listen on 'beforeMethod:PUT' due to order of operations with setting up the tree
		// so instead we limit ourselves to the PUT method manually
		if ($request->getMethod() !== 'PUT') {
			return;
		}
		try {
			$node = $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound $e) {
			return;
		}
		if ($node instanceof Node) {
			try {
				$node->acquireLock(ILockingProvider::LOCK_SHARED);
			} catch (LockedException $e) {
				throw new FileLocked($e->getMessage(), $e->getCode(), $e);
			}
			$this->isLocked = true;
		}
	}

	public function releaseLock(RequestInterface $request) {
		// don't try to release the lock if we never locked one
		if ($this->isLocked === false) {
			return;
		}
		if ($request->getMethod() !== 'PUT') {
			return;
		}
		try {
			$node = $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound $e) {
			return;
		}
		if ($node instanceof Node) {
			$node->releaseLock(ILockingProvider::LOCK_SHARED);
			$this->isLocked = false;
		}
	}
}
