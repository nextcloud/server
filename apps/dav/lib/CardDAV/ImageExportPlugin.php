<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\DAV\CardDAV;

use OCP\ILogger;
use Sabre\CardDAV\Card;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Parameter;
use Sabre\VObject\Property\Binary;
use Sabre\VObject\Reader;

class ImageExportPlugin extends ServerPlugin {

	/** @var Server */
	protected $server;
	/** @var ILogger */
	private $logger;

	public function __construct(ILogger $logger) {
		$this->logger = $logger;
	}

	/**
	 * Initializes the plugin and registers event handlers
	 *
	 * @param Server $server
	 * @return void
	 */
	function initialize(Server $server) {

		$this->server = $server;
		$this->server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * Intercepts GET requests on addressbook urls ending with ?photo.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool|void
	 */
	function httpGet(RequestInterface $request, ResponseInterface $response) {

		$queryParams = $request->getQueryParameters();
		// TODO: in addition to photo we should also add logo some point in time
		if (!array_key_exists('photo', $queryParams)) {
			return true;
		}

		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);

		if (!($node instanceof Card)) {
			return true;
		}

		$this->server->transactionType = 'carddav-image-export';

		// Checking ACL, if available.
		if ($aclPlugin = $this->server->getPlugin('acl')) {
			/** @var \Sabre\DAVACL\Plugin $aclPlugin */
			$aclPlugin->checkPrivileges($path, '{DAV:}read');
		}

		if ($result = $this->getPhoto($node)) {
			$response->setHeader('Content-Type', $result['Content-Type']);
			$response->setHeader('Content-Disposition', 'attachment');
			$response->setStatus(200);

			$response->setBody($result['body']);

			// Returning false to break the event chain
			return false;
		}
		return true;
	}

	function getPhoto(Card $node) {
		// TODO: this is kind of expensive - load carddav data from database and parse it
		//       we might want to build up a cache one day
		try {
			$vObject = $this->readCard($node->get());
			if (!$vObject->PHOTO) {
				return false;
			}

			$photo = $vObject->PHOTO;
			$type = $this->getType($photo);

			$val = $photo->getValue();
			if ($photo->getValueType() === 'URI') {
				$parsed = \Sabre\URI\parse($val);
				//only allow data://
				if ($parsed['scheme'] !== 'data') {
					return false;
				}
				if (substr_count($parsed['path'], ';') === 1) {
					list($type,) = explode(';', $parsed['path']);
				}
				$val = file_get_contents($val);
			}

			if (!in_array($type, ['image/png', 'image/jpeg', 'image/gif'])) {
				$type = 'application/octet-stream';
			}

			return [
				'Content-Type' => $type,
				'body' => $val
			];
		} catch(\Exception $ex) {
			$this->logger->logException($ex);
		}
		return false;
	}

	private function readCard($cardData) {
		return Reader::read($cardData);
	}

	/**
	 * @param Binary $photo
	 * @return string
	 */
	private function getType($photo) {
		$params = $photo->parameters();
		if (isset($params['TYPE']) || isset($params['MEDIATYPE'])) {
			/** @var Parameter $typeParam */
			$typeParam = isset($params['TYPE']) ? $params['TYPE'] : $params['MEDIATYPE'];
			$type = $typeParam->getValue();

			if (strpos($type, 'image/') === 0) {
				return $type;
			} else {
				return 'image/' . strtolower($type);
			}
		}
		return 'application/octet-stream';
	}
}
