<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Publishing;

use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\Publishing\Xml\Publisher;
use OCP\AppFramework\Http;
use OCP\IConfig;
use OCP\IURLGenerator;
use Sabre\CalDAV\Xml\Property\AllowedSharingModes;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class PublishPlugin extends ServerPlugin {
	public const NS_CALENDARSERVER = 'http://calendarserver.org/ns/';

	/**
	 * Reference to SabreDAV server object.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	/**
	 * PublishPlugin constructor.
	 *
	 * @param IConfig $config
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(
		/**
		 * Config instance to get instance secret.
		 */
		protected IConfig $config,
		/**
		 * URL Generator for absolute URLs.
		 */
		protected IURLGenerator $urlGenerator,
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
		// May have to be changed to be detected
		return ['oc-calendar-publishing', 'calendarserver-sharing'];
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
		return 'oc-calendar-publishing';
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
		$this->server->on('propFind', [$this, 'propFind']);
	}

	public function propFind(PropFind $propFind, INode $node) {
		if ($node instanceof Calendar) {
			$propFind->handle('{' . self::NS_CALENDARSERVER . '}publish-url', function () use ($node) {
				if ($node->getPublishStatus()) {
					// We return the publish-url only if the calendar is published.
					$token = $node->getPublishStatus();
					$publishUrl = $this->urlGenerator->getAbsoluteURL($this->server->getBaseUri() . 'public-calendars/') . $token;

					return new Publisher($publishUrl, true);
				}
			});

			$propFind->handle('{' . self::NS_CALENDARSERVER . '}allowed-sharing-modes', function () use ($node) {
				$canShare = (!$node->isSubscription() && $node->canWrite());
				$canPublish = (!$node->isSubscription() && $node->canWrite());

				if ($this->config->getAppValue('dav', 'limitAddressBookAndCalendarSharingToOwner', 'no') === 'yes') {
					$canShare = $canShare && ($node->getOwner() === $node->getPrincipalURI());
					$canPublish = $canPublish && ($node->getOwner() === $node->getPrincipalURI());
				}

				return new AllowedSharingModes($canShare, $canPublish);
			});
		}
	}

	/**
	 * We intercept this to handle POST requests on calendars.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 *
	 * @return void|bool
	 */
	public function httpPost(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();

		// Only handling xml
		$contentType = (string)$request->getHeader('Content-Type');
		if (!str_contains($contentType, 'application/xml') && !str_contains($contentType, 'text/xml')) {
			return;
		}

		// Making sure the node exists
		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return;
		}

		$requestBody = $request->getBodyAsString();

		// If this request handler could not deal with this POST request, it
		// will return 'null' and other plugins get a chance to handle the
		// request.
		//
		// However, we already requested the full body. This is a problem,
		// because a body can only be read once. This is why we preemptively
		// re-populated the request body with the existing data.
		$request->setBody($requestBody);

		$this->server->xml->parse($requestBody, $request->getUrl(), $documentType);

		switch ($documentType) {

			case '{' . self::NS_CALENDARSERVER . '}publish-calendar':

				// We can only deal with IShareableCalendar objects
				if (!$node instanceof Calendar) {
					return;
				}
				$this->server->transactionType = 'post-publish-calendar';

				// Getting ACL info
				$acl = $this->server->getPlugin('acl');

				// If there's no ACL support, we allow everything
				if ($acl) {
					/** @var \Sabre\DAVACL\Plugin $acl */
					$acl->checkPrivileges($path, '{DAV:}write');

					$limitSharingToOwner = $this->config->getAppValue('dav', 'limitAddressBookAndCalendarSharingToOwner', 'no') === 'yes';
					$isOwner = $acl->getCurrentUserPrincipal() === $node->getOwner();
					if ($limitSharingToOwner && !$isOwner) {
						return;
					}
				}

				$node->setPublishStatus(true);

				// iCloud sends back the 202, so we will too.
				$response->setStatus(Http::STATUS_ACCEPTED);

				// Adding this because sending a response body may cause issues,
				// and I wanted some type of indicator the response was handled.
				$response->setHeader('X-Sabre-Status', 'everything-went-well');

				// Breaking the event chain
				return false;

			case '{' . self::NS_CALENDARSERVER . '}unpublish-calendar':

				// We can only deal with IShareableCalendar objects
				if (!$node instanceof Calendar) {
					return;
				}
				$this->server->transactionType = 'post-unpublish-calendar';

				// Getting ACL info
				$acl = $this->server->getPlugin('acl');

				// If there's no ACL support, we allow everything
				if ($acl) {
					/** @var \Sabre\DAVACL\Plugin $acl */
					$acl->checkPrivileges($path, '{DAV:}write');

					$limitSharingToOwner = $this->config->getAppValue('dav', 'limitAddressBookAndCalendarSharingToOwner', 'no') === 'yes';
					$isOwner = $acl->getCurrentUserPrincipal() === $node->getOwner();
					if ($limitSharingToOwner && !$isOwner) {
						return;
					}
				}

				$node->setPublishStatus(false);

				$response->setStatus(Http::STATUS_OK);

				// Adding this because sending a response body may cause issues,
				// and I wanted some type of indicator the response was handled.
				$response->setHeader('X-Sabre-Status', 'everything-went-well');

				// Breaking the event chain
				return false;

		}
	}
}
