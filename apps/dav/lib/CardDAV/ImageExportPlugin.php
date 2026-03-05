<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCP\AppFramework\Http;
use OCP\Files\NotFoundException;
use Sabre\CardDAV\Card;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class ImageExportPlugin extends ServerPlugin {

	/** @var Server */
	protected $server;

	/**
	 * ImageExportPlugin constructor.
	 *
	 * @param PhotoCache $cache
	 */
	public function __construct(
		private PhotoCache $cache,
	) {
	}

	/**
	 * Initializes the plugin and registers event handlers
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * Intercepts GET requests on addressbook urls ending with ?photo.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response) {
		$queryParams = $request->getQueryParameters();
		// TODO: in addition to photo we should also add logo some point in time
		if (!array_key_exists('photo', $queryParams)) {
			return true;
		}

		$size = isset($queryParams['size']) ? (int)$queryParams['size'] : -1;

		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);

		if (!$node instanceof Card) {
			return true;
		}

		$this->server->transactionType = 'carddav-image-export';

		// Checking ACL, if available.
		if ($aclPlugin = $this->server->getPlugin('acl')) {
			/** @var \Sabre\DAVACL\Plugin $aclPlugin */
			$aclPlugin->checkPrivileges($path, '{DAV:}read');
		}

		// Fetch addressbook
		$addressbookpath = explode('/', $path);
		array_pop($addressbookpath);
		$addressbookpath = implode('/', $addressbookpath);
		/** @var AddressBook $addressbook */
		$addressbook = $this->server->tree->getNodeForPath($addressbookpath);

		$response->setHeader('Cache-Control', 'private, max-age=3600, must-revalidate');
		$response->setHeader('Etag', $node->getETag());

		try {
			$file = $this->cache->get($addressbook->getResourceId(), $node->getName(), $size, $node);
			$response->setHeader('Content-Type', $file->getMimeType());
			$fileName = $node->getName() . '.' . PhotoCache::ALLOWED_CONTENT_TYPES[$file->getMimeType()];
			$response->setHeader('Content-Disposition', "attachment; filename=$fileName");
			$response->setStatus(Http::STATUS_OK);

			$response->setBody($file->getContent());
		} catch (NotFoundException $e) {
			$response->setStatus(Http::STATUS_NO_CONTENT);
		}

		return false;
	}
}
