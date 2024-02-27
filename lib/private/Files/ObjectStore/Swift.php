<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Adrian Brzezinski <adrian.brzezinski@eo.pl>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Files\ObjectStore;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Utils;
use Icewind\Streams\RetryWrapper;
use OCP\Files\NotFoundException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\StorageAuthException;
use Psr\Log\LoggerInterface;

const SWIFT_SEGMENT_SIZE = 1073741824; // 1GB

class Swift implements IObjectStore {
	/**
	 * @var array
	 */
	private $params;

	/** @var SwiftFactory */
	private $swiftFactory;

	public function __construct($params, SwiftFactory $connectionFactory = null) {
		$this->swiftFactory = $connectionFactory ?: new SwiftFactory(
			\OC::$server->getMemCacheFactory()->createDistributed('swift::'),
			$params,
			\OC::$server->get(LoggerInterface::class)
		);
		$this->params = $params;
	}

	/**
	 * @return \OpenStack\ObjectStore\v1\Models\Container
	 * @throws StorageAuthException
	 * @throws \OCP\Files\StorageNotAvailableException
	 */
	private function getContainer() {
		return $this->swiftFactory->getContainer();
	}

	/**
	 * @return string the container name where objects are stored
	 */
	public function getStorageId() {
		if (isset($this->params['bucket'])) {
			return $this->params['bucket'];
		}

		return $this->params['container'];
	}

	public function writeObject($urn, $stream, string $mimetype = null) {
		$tmpFile = \OC::$server->getTempManager()->getTemporaryFile('swiftwrite');
		file_put_contents($tmpFile, $stream);
		$handle = fopen($tmpFile, 'rb');

		if (filesize($tmpFile) < SWIFT_SEGMENT_SIZE) {
			$this->getContainer()->createObject([
				'name' => $urn,
				'stream' => Utils::streamFor($handle),
				'contentType' => $mimetype,
			]);
		} else {
			$this->getContainer()->createLargeObject([
				'name' => $urn,
				'stream' => Utils::streamFor($handle),
				'segmentSize' => SWIFT_SEGMENT_SIZE,
				'contentType' => $mimetype,
			]);
		}
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception from openstack or GuzzleHttp libs when something goes wrong
	 * @throws NotFoundException if file does not exist
	 */
	public function readObject($urn) {
		try {
			$publicUri = $this->getContainer()->getObject($urn)->getPublicUri();
			$tokenId = $this->swiftFactory->getCachedTokenId();

			$response = (new Client())->request('GET', $publicUri,
				[
					'stream' => true,
					'headers' => [
						'X-Auth-Token' => $tokenId,
						'Cache-Control' => 'no-cache',
					],
				]
			);
		} catch (BadResponseException $e) {
			if ($e->getResponse() && $e->getResponse()->getStatusCode() === 404) {
				throw new NotFoundException("object $urn not found in object store");
			} else {
				throw $e;
			}
		}

		return RetryWrapper::wrap($response->getBody()->detach());
	}

	/**
	 * @param string $urn Unified Resource Name
	 * @return void
	 * @throws \Exception from openstack lib when something goes wrong
	 */
	public function deleteObject($urn) {
		$this->getContainer()->getObject($urn)->delete();
	}

	/**
	 * @return void
	 * @throws \Exception from openstack lib when something goes wrong
	 */
	public function deleteContainer() {
		$this->getContainer()->delete();
	}

	public function objectExists($urn) {
		return $this->getContainer()->objectExists($urn);
	}

	public function copyObject($from, $to) {
		$this->getContainer()->getObject($from)->copy([
			'destination' => $this->getContainer()->name . '/' . $to
		]);
	}
}
