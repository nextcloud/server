<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

	public function __construct($params) {
		if (!isset($params['container'])) {
			$params['container'] = 'owncloud';
		}
		if (!isset($params['autocreate'])) {
			// should only be true for tests
			$params['autocreate'] = false;
		}

		if (isset($params['apiKey'])) {
			$this->client = new Rackspace($params['url'], $params);
		} else {
			$this->client = new OpenStack($params['url'], $params);
		}
		$this->params = $params;
	}

	protected function init() {
		if ($this->container) {
			return;
		}

		// the OpenCloud client library will default to 'cloudFiles' if $serviceName is null
		$serviceName = null;
		if (isset($this->params['serviceName'])) {
			$serviceName = $this->params['serviceName'];
		}

		// the OpenCloud client library will default to 'publicURL' if $urlType is null
		$urlType = null;
		if (isset($this->params['urlType'])) {
			$urlType = $this->params['urlType'];
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
		stream_context_set_option($stream, 'swift','content', $objectContent);

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
