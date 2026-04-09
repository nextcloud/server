<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\BirthdayCalendar;

use OCA\DAV\CalDAV\BirthdayService;
use OCA\DAV\CalDAV\CalendarHome;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IUser;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Class EnablePlugin
 * allows users to re-enable the birthday calendar via CalDAV
 *
 * @package OCA\DAV\CalDAV\BirthdayCalendar
 */
class EnablePlugin extends ServerPlugin {
	public const NS_Nextcloud = 'http://nextcloud.com/ns';

	/**
	 * @var Server
	 */
	protected $server;

	/**
	 * PublishPlugin constructor.
	 *
	 * @param IConfig $config
	 * @param BirthdayService $birthdayService
	 * @param IUser $user
	 */
	public function __construct(
		protected IConfig $config,
		protected BirthdayService $birthdayService,
		private IUser $user,
	) {
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures() {
		return ['nc-enable-birthday-calendar'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return 'nc-enable-birthday-calendar';
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

		$this->server->on('method:POST', [$this, 'httpPost']);
	}

	/**
	 * We intercept this to handle POST requests on calendar homes.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 *
	 * @return bool|void
	 */
	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		$node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
		if (!$node instanceof CalendarHome) {
			return;
		}

		$requestBody = $request->getBodyAsString();
		$this->server->xml->parse($requestBody, $request->getUrl(), $documentType);
		if ($documentType !== '{' . self::NS_Nextcloud . '}enable-birthday-calendar') {
			return;
		}

		$owner = substr($node->getOwner(), 17);
		if ($owner !== $this->user->getUID()) {
			$this->server->httpResponse->setStatus(Http::STATUS_FORBIDDEN);
			return false;
		}

		$this->config->setUserValue($this->user->getUID(), 'dav', 'generateBirthdayCalendar', 'yes');
		$this->birthdayService->syncUser($this->user->getUID());

		$this->server->httpResponse->setStatus(Http::STATUS_NO_CONTENT);

		return false;
	}
}
