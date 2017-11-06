<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author William Pain <pain.william@gmail.com>
 *
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

namespace OC\Files\ObjectStore;

use Guzzle\Http\Exception\ClientErrorResponseException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\StorageAuthException;
use OCP\Files\StorageNotAvailableException;
use OpenCloud\Common\Service\Catalog;
use OpenCloud\Common\Service\CatalogItem;
use OpenCloud\Identity\Resource\Token;
use OpenCloud\ObjectStore\Service;
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;

class Swift implements IObjectStore {

	/**
	 * @var \OpenCloud\OpenStack
	 */
	private $client;

	/**
	 * @var array
	 */
	private $params;

	/**
	 * @var \OpenCloud\ObjectStore\Service
	 */
	private $objectStoreService;

	/**
	 * @var \OpenCloud\ObjectStore\Resource\Container
	 */
	private $container;

	private $memcache;

	public function __construct($params) {
		if (isset($params['bucket'])) {
			$params['container'] = $params['bucket'];
		}
		if (!isset($params['container'])) {
			$params['container'] = 'owncloud';
		}
		if (!isset($params['autocreate'])) {
			// should only be true for tests
			$params['autocreate'] = false;
		}

		if (isset($params['apiKey'])) {
			$this->client = new Rackspace($params['url'], $params);
			$cacheKey = $params['username'] . '@' . $params['url'] . '/' . $params['bucket'];
		} else {
			$this->client = new OpenStack($params['url'], $params);
			$cacheKey = $params['username'] . '@' . $params['url'] . '/' . $params['bucket'];
		}

		$cacheFactory = \OC::$server->getMemCacheFactory();
		$this->memcache = $cacheFactory->create('swift::' . $cacheKey);

		$this->params = $params;
	}

	protected function init() {
		if ($this->container) {
			return;
		}

		$this->importToken();

		/** @var Token $token */
		$token = $this->client->getTokenObject();

		if (!$token || $token->hasExpired()) {
			try {
				$this->client->authenticate();
				$this->exportToken();
			} catch (ClientErrorResponseException $e) {
				$statusCode = $e->getResponse()->getStatusCode();
				if ($statusCode == 412) {
					throw new StorageAuthException('Precondition failed, verify the keystone url', $e);
				} else if ($statusCode === 401) {
					throw new StorageAuthException('Authentication failed, verify the username, password and possibly tenant', $e);
				} else {
					throw new StorageAuthException('Unknown error', $e);
				}
			}
		}


		/** @var Catalog $catalog */
		$catalog = $this->client->getCatalog();

		if (isset($this->params['serviceName'])) {
			$serviceName = $this->params['serviceName'];
		} else {
			$serviceName = Service::DEFAULT_NAME;
		}

		if (isset($this->params['urlType'])) {
			$urlType = $this->params['urlType'];
			if ($urlType !== 'internalURL' && $urlType !== 'publicURL') {
				throw new StorageNotAvailableException('Invalid url type');
			}
		} else {
			$urlType = Service::DEFAULT_URL_TYPE;
		}

		$catalogItem = $this->getCatalogForService($catalog, $serviceName);
		if (!$catalogItem) {
			$available = implode(', ', $this->getAvailableServiceNames($catalog));
			throw new StorageNotAvailableException(
				"Service $serviceName not found in service catalog, available services: $available"
			);
		} else if (isset($this->params['region'])) {
			$this->validateRegion($catalogItem, $this->params['region']);
		}

		$this->objectStoreService = $this->client->objectStoreService($serviceName, $this->params['region'], $urlType);

		try {
			$this->container = $this->objectStoreService->getContainer($this->params['container']);
		} catch (ClientErrorResponseException $ex) {
			// if the container does not exist and autocreate is true try to create the container on the fly
			if (isset($this->params['autocreate']) && $this->params['autocreate'] === true) {
				$this->container = $this->objectStoreService->createContainer($this->params['container']);
			} else {
				throw $ex;
			}
		}
	}

	private function exportToken() {
		$export = $this->client->exportCredentials();
		$export['catalog'] = array_map(function (CatalogItem $item) {
			return [
				'name' => $item->getName(),
				'endpoints' => $item->getEndpoints(),
				'type' => $item->getType()
			];
		}, $export['catalog']->getItems());
		$this->memcache->set('token', json_encode($export));
	}

	private function importToken() {
		$cachedTokenString = $this->memcache->get('token');
		if ($cachedTokenString) {
			$cachedToken = json_decode($cachedTokenString, true);
			$cachedToken['catalog'] = array_map(function (array $item) {
				$itemClass = new \stdClass();
				$itemClass->name = $item['name'];
				$itemClass->endpoints = array_map(function (array $endpoint) {
					return (object) $endpoint;
				}, $item['endpoints']);
				$itemClass->type = $item['type'];

				return $itemClass;
			}, $cachedToken['catalog']);
			try {
				$this->client->importCredentials($cachedToken);
			} catch (\Exception $e) {
				$this->client->setTokenObject(new Token());
			}
		}
	}

	/**
	 * @param Catalog $catalog
	 * @param $name
	 * @return null|CatalogItem
	 */
	private function getCatalogForService(Catalog $catalog, $name) {
		foreach ($catalog->getItems() as $item) {
			/** @var CatalogItem $item */
			if ($item->hasType(Service::DEFAULT_TYPE) && $item->hasName($name)) {
				return $item;
			}
		}

		return null;
	}

	private function validateRegion(CatalogItem $item, $region) {
		$endPoints = $item->getEndpoints();
		foreach ($endPoints as $endPoint) {
			if ($endPoint->region === $region) {
				return;
			}
		}

		$availableRegions = implode(', ', array_map(function ($endpoint) {
			return $endpoint->region;
		}, $endPoints));

		throw new StorageNotAvailableException("Invalid region '$region', available regions: $availableRegions");
	}

	private function getAvailableServiceNames(Catalog $catalog) {
		return array_map(function (CatalogItem $item) {
			return $item->getName();
		}, array_filter($catalog->getItems(), function (CatalogItem $item) {
			return $item->hasType(Service::DEFAULT_TYPE);
		}));
	}

	/**
	 * @return string the container name where objects are stored
	 */
	public function getStorageId() {
		return $this->params['container'];
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function writeObject($urn, $stream) {
		$this->init();
		$this->container->uploadObject($urn, $stream);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function readObject($urn) {
		$this->init();
		$object = $this->container->getObject($urn);

		// we need to keep a reference to objectContent or
		// the stream will be closed before we can do anything with it
		/** @var $objectContent \Guzzle\Http\EntityBody * */
		$objectContent = $object->getContent();
		$objectContent->rewind();

		$stream = $objectContent->getStream();
		// save the object content in the context of the stream to prevent it being gc'd until the stream is closed
		stream_context_set_option($stream, 'swift', 'content', $objectContent);

		return $stream;
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @return void
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function deleteObject($urn) {
		$this->init();
		// see https://github.com/rackspace/php-opencloud/issues/243#issuecomment-30032242
		$this->container->dataObject()->setName($urn)->delete();
	}

	public function deleteContainer($recursive = false) {
		$this->init();
		$this->container->delete($recursive);
	}

}
