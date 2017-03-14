<?php
/**
 * @copyright Copyright (c) 2017 Yunify, Inc.
 *
 * @author: Xuanwo <xuanwo@yunify.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use OCP\Files\ObjectStore\IObjectStore;
use QingStor\SDK\Service\QingStor as QingStorService;
use QingStor\SDK\Config as QingStorConfig;

class QingStor implements IObjectStore {

	/** @var array */
	protected $params;

	/** @var string */
	protected $id;

	/** @var  QingStor\SDK\Service\Bucket */
	protected $bucket;

	protected function parseParams($params) {
		if (empty($params['key']) || empty($params['secret']) || empty($params['bucket'])) {
			throw new \Exception('Access Key, Secret Key, and Bucket must be configured.');
		}

		$this->id = 'qingstor::' . $params['bucket'];

		$params['zone'] = empty($params['zone']) ? 'pek3a' : $params['zone'];
		$params['host'] = empty($params['host']) ? 'qingstor.com' : $params['host'];
		$params['part_size'] = empty($params['part_size']) ? 64 * 1024 * 1024 : $params['part_size'] * 1024 * 1024;
		$params['prefix'] = empty($params['prefix']) ? '' : $params['prefix'];
		if (!isset($params['port']) || $params['port'] === '') {
			$params['port'] = (isset($params['use_ssl']) && $params['use_ssl'] === false) ? 80 : 443;
		}
		$this->params = $params;
	}

	protected function getBucket() {
		if (!is_null($this->bucket)) {
			return $this->bucket;
		}

		$scheme = (isset($this->params['use_ssl']) && $this->params['use_ssl'] === false) ? 'http' : 'https';

		$config = new QingStorConfig();
		$config->access_key_id = $this->params['key'];
		$config->secret_access_key = $this->params['secret'];
		$config->host = $this->params['host'];
		$config->port = $this->params['port'];
		$config->protocol = $scheme;

		$service = new QingStorService($config);
		$this->bucket = $service->Bucket($this->params['bucket'], $this->params['zone']);
		return $this->bucket;
	}

	public function __construct($parameters) {
		$this->parseParams($parameters);
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 * @since 7.0.0
	 */
	function getStorageId() {
		return $this->id;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function readObject($urn) {
		$req = $this->getBucket()->getObjectQuery($urn, time() + 1000);

		$headers = $req->getHeaders();
		$headers[] = 'Connection: close';

		$opts = [
			'http' => [
				'method' => 'GET',
				'header' => $headers
			],
			'ssl' => [
				'verify_peer' => true
			]
		];

		$context = stream_context_create($opts);
		return fopen($req->getUrl(), 'r', false, $context);
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @param resource $stream stream with the data to write
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function writeObject($urn, $stream) {
		$content = fread($stream, $this->params['part_size']);
		if (strlen($content) < $this->params['part_size']) {
			$this->getBucket()->putObject(
				$urn,
				array(
					'body' => $content
				)
			);
		} else {
			$this->multipartUpload($urn, $stream);
		}
	}

	/**
	 * @param $urn
	 * @param $stream
	 * @return bool
	 */
	function multipartUpload($urn, $stream) {
		rewind($stream);
		$bucket = $this->getBucket();
		$upload_id = $bucket->initiateMultipartUpload($urn)->upload_id;
		$parts = array();
		$part_number = 0;
		$content = fread($stream, $this->params['part_size']);
		while ($content) {
			try {
				$bucket->uploadMultipart($urn, array(
					'upload_id' => $upload_id,
					'part_number' => $part_number,
					'body' => $content
				));
			} catch (\Exception $ex) {
				\OCP\Util::writeLog('QingStor', $ex->getMessage(), \OCP\Util::ERROR);
				$bucket->abortMultipartUpload($urn, array(
					'upload_id' => $upload_id
				));
				return false;
			}
			$parts[] = array(
				'part_number' => $part_number
			);
			$part_number += 1;
			$content = fread($stream, $this->params['part_size']);
		}
		$bucket->completeMultipartUpload($urn, array(
			'upload_id' => $upload_id,
			'object_parts' => $parts
		));
		return true;
	}


	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	function deleteObject($urn) {
		$this->getBucket()->deleteObject($urn);
	}
}