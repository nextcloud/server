<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\AppFramework\Http;
use Sabre\DAV\INode;
use Sabre\DAV\Locks\LockInfo;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Xml\Property\LockDiscovery;
use Sabre\DAV\Xml\Property\SupportedLock;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Class FakeLockerPlugin is a plugin only used when connections come in from
 * OS X via Finder. The fake locking plugin does emulate Class 2 WebDAV support
 * (locking of files) which allows Finder to access the storage in write mode as
 * well.
 *
 * No real locking is performed, instead the plugin just returns always positive
 * responses.
 *
 * @see https://github.com/owncloud/core/issues/17732
 * @package OCA\DAV\Connector\Sabre
 */
class FakeLockerPlugin extends ServerPlugin {
	/** @var Server */
	private $server;

	/** {@inheritDoc} */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('method:LOCK', [$this, 'fakeLockProvider'], 1);
		$this->server->on('method:UNLOCK', [$this, 'fakeUnlockProvider'], 1);
		$server->on('propFind', [$this, 'propFind']);
		$server->on('validateTokens', [$this, 'validateTokens']);
	}

	/**
	 * Indicate that we support LOCK and UNLOCK
	 *
	 * @param string $path
	 * @return string[]
	 */
	public function getHTTPMethods($path) {
		return [
			'LOCK',
			'UNLOCK',
		];
	}

	/**
	 * Indicate that we support locking
	 *
	 * @return integer[]
	 */
	public function getFeatures() {
		return [2];
	}

	/**
	 * Return some dummy response for PROPFIND requests with regard to locking
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 * @return void
	 */
	public function propFind(PropFind $propFind, INode $node) {
		$propFind->handle('{DAV:}supportedlock', function () {
			return new SupportedLock();
		});
		$propFind->handle('{DAV:}lockdiscovery', function () use ($propFind) {
			return new LockDiscovery([]);
		});
	}

	/**
	 * Mark a locking token always as valid
	 *
	 * @param RequestInterface $request
	 * @param array $conditions
	 */
	public function validateTokens(RequestInterface $request, &$conditions) {
		foreach ($conditions as &$fileCondition) {
			if (isset($fileCondition['tokens'])) {
				foreach ($fileCondition['tokens'] as &$token) {
					if (isset($token['token'])) {
						if (str_starts_with($token['token'], 'opaquelocktoken:')) {
							$token['validToken'] = true;
						}
					}
				}
			}
		}
	}

	/**
	 * Fakes a successful LOCK
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 */
	public function fakeLockProvider(RequestInterface $request,
		ResponseInterface $response) {
		$lockInfo = new LockInfo();
		$lockInfo->token = md5($request->getPath());
		$lockInfo->uri = $request->getPath();
		$lockInfo->depth = Server::DEPTH_INFINITY;
		$lockInfo->timeout = 1800;

		$body = $this->server->xml->write('{DAV:}prop', [
			'{DAV:}lockdiscovery'
					=> new LockDiscovery([$lockInfo])
		]);

		$response->setStatus(Http::STATUS_OK);
		$response->setBody($body);

		return false;
	}

	/**
	 * Fakes a successful LOCK
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 */
	public function fakeUnlockProvider(RequestInterface $request,
		ResponseInterface $response) {
		$response->setStatus(Http::STATUS_NO_CONTENT);
		$response->setHeader('Content-Length', '0');
		return false;
	}
}
