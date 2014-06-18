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
use OpenCloud\OpenStack;

class Swift implements \OCP\Files\ObjectStore\IObjectStore {

	/**
	 * @var \OpenCloud\ObjectStore\Service
	 */
	private $objectStoreService;
	
	/**
	 * @var \OpenCloud\ObjectStore\Resource\Container
	 */
	private $container;

	public function __construct($params) {
		if (!isset($params['username']) || !isset($params['password']) ) {
			throw new \Exception("Access Key and Secret have to be configured.");
		}
		if (!isset($params['container'])) {
			$params['container'] = 'owncloud';
		}
		if (!isset($params['autocreate'])) {
			// should only be true for tests
			$params['autocreate'] = false;
		}

		$secret = array(
			'username' => $params['username'],
			'password' => $params['password']
		);
		if (isset($params['tenantName'])) {
			$secret['tenantName'] = $params['tenantName'];
		}
		if (isset($params['tenantId'])) {
			$secret['tenantId'] = $params['tenantId'];
		}

		// the OpenCloud client library will default to 'cloudFiles' if $serviceName is null
		$serviceName = null;
		if ($params['serviceName']) {
			$serviceName = $params['serviceName'];
		}

		$client = new OpenStack($params['url'], $secret);

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

	public function getStorageId() {
		return $this->container->name;
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @param string $tmpFile
	 * @return void
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function writeObject($urn, $tmpFile = null) {
		$fileData = '';
		if ($tmpFile) {
			$fileData = fopen($tmpFile, 'r');
		}

		$this->container->uploadObject($urn, $fileData);
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @param string $tmpFile
	 * @return void
	 * @throws Exception from openstack lib when something goes wrong
	 */
	public function getObject($urn, $tmpFile) {
		$object = $this->container->getObject($urn);

		/** @var $objectContent \Guzzle\Http\EntityBody **/
		$objectContent = $object->getContent();

		$objectContent->rewind();
		$stream = $objectContent->getStream();
		file_put_contents($tmpFile, $stream);
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
