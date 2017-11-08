<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
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

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

trait S3ConnectionTrait {
	/** @var array */
	protected $params;

	/** @var S3Client */
	protected $connection;

	/** @var string */
	protected $id;

	/** @var string */
	protected $bucket;

	/** @var int */
	protected $timeout;

	protected $test;

	protected function parseParams($params) {
		if (empty($params['key']) || empty($params['secret']) || empty($params['bucket'])) {
			throw new \Exception("Access Key, Secret and Bucket have to be configured.");
		}

		$this->id = 'amazon::' . $params['bucket'];

		$this->test = isset($params['test']);
		$this->bucket = $params['bucket'];
		$this->timeout = (!isset($params['timeout'])) ? 15 : $params['timeout'];
		$params['region'] = empty($params['region']) ? 'eu-west-1' : $params['region'];
		$params['hostname'] = empty($params['hostname']) ? 's3.' . $params['region'] . '.amazonaws.com' : $params['hostname'];
		if (!isset($params['port']) || $params['port'] === '') {
			$params['port'] = (isset($params['use_ssl']) && $params['use_ssl'] === false) ? 80 : 443;
		}
		$this->params = $params;
	}


	/**
	 * Returns the connection
	 *
	 * @return S3Client connected client
	 * @throws \Exception if connection could not be made
	 */
	protected function getConnection() {
		if (!is_null($this->connection)) {
			return $this->connection;
		}

		$scheme = (isset($this->params['use_ssl']) && $this->params['use_ssl'] === false) ? 'http' : 'https';
		$base_url = $scheme . '://' . $this->params['hostname'] . ':' . $this->params['port'] . '/';

		$options = [
			'version' => isset($this->params['version']) ? $this->params['version'] : 'latest',
			'credentials' => [
				'key' => $this->params['key'],
				'secret' => $this->params['secret'],
			],
			'endpoint' => $base_url,
			'region' => $this->params['region'],
			'use_path_style_endpoint' => isset($this->params['use_path_style']) ? $this->params['use_path_style'] : false
		];
		if (isset($this->params['proxy'])) {
			$options['request.options'] = ['proxy' => $this->params['proxy']];
		}
		$this->connection = new S3Client($options);

		if (!$this->connection->isBucketDnsCompatible($this->bucket)) {
			throw new \Exception("The configured bucket name is invalid: " . $this->bucket);
		}

		if (!$this->connection->doesBucketExist($this->bucket)) {
			try {
				$this->connection->createBucket(array(
					'Bucket' => $this->bucket
				));
				$this->testTimeout();
			} catch (S3Exception $e) {
				\OCP\Util::logException('files_external', $e);
				throw new \Exception('Creation of bucket failed. ' . $e->getMessage());
			}
		}

		return $this->connection;
	}

	/**
	 * when running the tests wait to let the buckets catch up
	 */
	private function testTimeout() {
		if ($this->test) {
			sleep($this->timeout);
		}
	}
}
