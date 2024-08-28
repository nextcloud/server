<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Connector\Sabre;

use Sabre\DAV\CorePlugin;
use Sabre\DAV\FS\Directory;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Tree;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class AnonymousOptionsPlugin extends ServerPlugin {

	/**
	 * @var \Sabre\DAV\Server
	 */
	private $server;

	/**
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$this->server = $server;
		// before auth
		$this->server->on('beforeMethod:*', [$this, 'handleAnonymousOptions'], 9);
	}

	/**
	 * @return bool
	 */
	public function isRequestInRoot($path) {
		return $path === '' || (is_string($path) && !str_contains($path, '/'));
	}

	/**
	 * @throws \Sabre\DAV\Exception\Forbidden
	 * @return bool
	 */
	public function handleAnonymousOptions(RequestInterface $request, ResponseInterface $response) {
		$isOffice = preg_match('/Microsoft Office/i', $request->getHeader('User-Agent') ?? '');
		$emptyAuth = $request->getHeader('Authorization') === null
			|| $request->getHeader('Authorization') === ''
			|| trim($request->getHeader('Authorization')) === 'Bearer';
		$isAnonymousOfficeOption = $request->getMethod() === 'OPTIONS' && $isOffice && $emptyAuth;
		$isOfficeHead = $request->getMethod() === 'HEAD' && $isOffice && $emptyAuth;
		if ($isAnonymousOfficeOption || $isOfficeHead) {
			/** @var CorePlugin $corePlugin */
			$corePlugin = $this->server->getPlugin('core');
			// setup a fake tree for anonymous access
			$this->server->tree = new Tree(new Directory(''));
			$corePlugin->httpOptions($request, $response);
			$this->server->emit('afterMethod:*', [$request, $response]);
			$this->server->emit('afterMethod:OPTIONS', [$request, $response]);

			$this->server->sapi->sendResponse($response);
			return false;
		}
	}
}
