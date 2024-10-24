<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\WebcalCaching;

use OCA\DAV\CalDAV\CalendarRoot;
use OCP\IRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

	/**
	 * list of regular expressions for calendar user agents,
	 * that do not support subscriptions on their own
	 *
	 * /^MSFT-WIN-3/ - Windows 10 Calendar
	 * /Evolution/ - Gnome Calendar/Evolution
	 * /KIO/ - KDE PIM/Akonadi
	 * @var string[]
	 */
	public const ENABLE_FOR_CLIENTS = [
		'/^MSFT-WIN-3/',
		'/Evolution/',
		'/KIO/'
	];

	/**
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * @var Server
	 */
	private $server;

	/**
	 * Plugin constructor.
	 *
	 * @param IRequest $request
	 */
	public function __construct(IRequest $request) {
		if ($request->isUserAgent(self::ENABLE_FOR_CLIENTS)) {
			$this->enabled = true;
		}

		$magicHeader = $request->getHeader('X-NC-CalDAV-Webcal-Caching');
		if ($magicHeader === 'On') {
			$this->enabled = true;
		}

		$isExportRequest = $request->getMethod() === 'GET' && array_key_exists('export', $request->getParams());
		if ($isExportRequest) {
			$this->enabled = true;
		}
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$server->on('beforeMethod:*', [$this, 'beforeMethod'], 15);
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 */
	public function beforeMethod(RequestInterface $request, ResponseInterface $response) {
		if (!$this->enabled) {
			return;
		}

		$path = $request->getPath();
		if (!str_starts_with($path, 'calendars/')) {
			return;
		}

		$pathParts = explode('/', ltrim($path, '/'));
		if (\count($pathParts) < 2) {
			return;
		}

		try {
			$calendarRoot = $this->server->tree->getNodeForPath($pathParts[0]);
			if ($calendarRoot instanceof CalendarRoot) {
				$calendarRoot->enableReturnCachedSubscriptions($pathParts[1]);
			}
		} catch (NotFound $ex) {
			return;
		}
	}

	/**
	 * @return bool
	 */
	public function isCachingEnabledForThisRequest():bool {
		return $this->enabled;
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures():array {
		return ['nc-calendar-webcal-cache'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName():string {
		return 'nc-calendar-webcal-cache';
	}
}
