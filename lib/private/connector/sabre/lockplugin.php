<?php
/**
 * @author Robin Appelman <icewind@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Connector\Sabre;

use OC\Connector\Sabre\Exception\FileLocked;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use Sabre\DAV\Exception\NotFound;
use \Sabre\DAV\PropFind;
use \Sabre\DAV\PropPatch;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class LockPlugin extends ServerPlugin {
	/**
	 * Reference to main server object
	 *
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @var \Sabre\DAV\Tree
	 */
	private $tree;

	/**
	 * @param \Sabre\DAV\Tree $tree tree
	 */
	public function __construct(Tree $tree) {
		$this->tree = $tree;
	}

	/**
	 * {@inheritdoc}
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;
		$this->server->on('beforeMethod', [$this, 'getLock'], 50);
		$this->server->on('afterMethod', [$this, 'releaseLock'], 50);
	}

	public function getLock(RequestInterface $request) {
		// we cant listen on 'beforeMethod:PUT' due to order of operations with setting up the tree
		// so instead we limit ourselves to the PUT method manually
		if ($request->getMethod() !== 'PUT') {
			return;
		}
		try {
			$node = $this->tree->getNodeForPath($request->getPath());
		} catch (NotFound $e) {
			return;
		}
		if ($node instanceof Node) {
			try {
				$node->acquireLock(ILockingProvider::LOCK_SHARED);
			} catch (LockedException $e) {
				throw new FileLocked($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	public function releaseLock(RequestInterface $request) {
		if ($request->getMethod() !== 'PUT') {
			return;
		}
		try {
			$node = $this->tree->getNodeForPath($request->getPath());
		} catch (NotFound $e) {
			return;
		}
		if ($node instanceof Node) {
			$node->releaseLock(ILockingProvider::LOCK_SHARED);
		}
	}
}
