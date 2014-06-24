<?php
/**
 * @author Jörn Friedrich Dreyer
 * @copyright (c) 2014 Jörn Friedrich Dreyer <jfd@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use Guzzle\Http\Exception\ClientErrorResponseException;
use OCP\Files\ObjectStore\IObjectStore;
use OpenCloud\OpenStack;
use OpenCloud\Rackspace;

class Swift implements IObjectStore {

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

		// the OpenCloud client library will default to 'cloudFiles' if $serviceName is null
		$serviceName = null;
		if ($params['serviceName']) {
			$serviceName = $params['serviceName'];
		}

		if (isset($params['apiKey'])) {
			$client = new Rackspace($params['url'], $params);
		} else {
			$client = new OpenStack($params['url'], $params);
		}

		$this->objectStoreService = $client->objectStoreService($serviceName, $params['region']);

		try {
			$this->container = $this->objectStoreService->getContainer($params['container']);
		} catch (ClientErrorResponseException $ex) {
			// if the container does not exist and autocreate is true try to create the container on the fly
			if (isset($params['autocreate']) && $params['autocreate'] === true) {
				$this->container = $this->objectStoreService->createContainer($params['container']);
			} else {
				throw $ex;
			}
		}
	}

	/**
	 * @return string the container name where objects are stored
	 */
	public function getStorageId() {
		return $this->container->name;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function writeObject($urn, $stream) {
		$this->container->uploadObject($urn, $stream);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function readObject($urn) {
		$object = $this->container->getObject($urn);

		// we need to keep a reference to objectContent or
		// the stream will be closed before we can do anything with it
		/** @var $objectContent \Guzzle\Http\EntityBody * */
		$objectContent = $object->getContent();
		$objectContent->rewind();

		// directly returning the object stream does not work because the GC seems to collect it, so we need a copy
		$tmpStream = fopen('php://temp', 'r+');
		stream_copy_to_stream($objectContent->getStream(), $tmpStream);
		rewind($tmpStream);

		return $tmpStream;
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @return void
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function deleteObject($urn) {
		$object = $this->container->getObject($urn);
		$object->delete();
	}

	public function deleteContainer($recursive = false) {
		$this->container->delete($recursive);
	}

}
