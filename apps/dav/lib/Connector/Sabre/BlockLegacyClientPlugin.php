<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Connector\Sabre;

use OCP\IConfig;
use OCP\IRequest;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;

/**
 * Class BlockLegacyClientPlugin is used to detect old legacy sync clients and
 * returns a 403 status to those clients
 *
 * @package OCA\DAV\Connector\Sabre
 */
class BlockLegacyClientPlugin extends ServerPlugin {
	protected ?Server $server = null;
	protected IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('beforeMethod:*', [$this, 'beforeHandler'], 200);
	}

	/**
	 * Detects all unsupported clients and throws a \Sabre\DAV\Exception\Forbidden
	 * exception which will result in a 403 to them.
	 * @param RequestInterface $request
	 * @throws \Sabre\DAV\Exception\Forbidden If the client version is not supported
	 */
	public function beforeHandler(RequestInterface $request) {
		$userAgent = $request->getHeader('User-Agent');
		if ($userAgent === null) {
			return;
		}

		$minimumSupportedDesktopVersion = $this->config->getSystemValue('minimum.supported.desktop.version', '2.3.0');
		preg_match(IRequest::USER_AGENT_CLIENT_DESKTOP, $userAgent, $versionMatches);
		if (isset($versionMatches[1]) &&
			version_compare($versionMatches[1], $minimumSupportedDesktopVersion) === -1) {
			throw new \Sabre\DAV\Exception\Forbidden('Unsupported client version.');
		}
	}
}
