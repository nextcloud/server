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

use OpenCloud\OpenStack;

class Swift extends AbstractObjectStore {

	
	/**
	 * @var \OpenCloud\ObjectStore\Resource\Container
	 */
	private $container;

	public function __construct($params) {
		if (!isset($params['username']) || !isset($params['password']) ) {
			throw new \Exception("Access Key and Secret have to be configured.");
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

		/** @var $objectStoreService \OpenCloud\ObjectStore\Service **/
		$objectStoreService = $client->objectStoreService($serviceName, $params['region']);

		$this->container = $objectStoreService->getContainer($params['container']);

		//set the user via parent constructor, also initializes the root of the filecache
		parent::__construct($params);
	}

	protected function deleteObject($urn) {
		$object = $this->container->getObject($urn);
		$object->delete();
	}
	
	protected function getObject($urn, $tmpFile) {
		$object = $this->container->getObject($urn);

		/** @var $objectContent \Guzzle\Http\EntityBody **/
		$objectContent = $object->getContent();

		$objectContent->rewind();
		$stream = $objectContent->getStream();
		file_put_contents($tmpFile, $stream);
	}
	
	protected function createObject($urn, $tmpFile = null) {
		$fileData = '';
		if ($tmpFile) {
			$fileData = fopen($tmpFile, 'r');
		}

		$this->container->uploadObject($urn, $fileData);
	}

}
