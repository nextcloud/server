<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Florent <florent@coppint.com>
 * @author James Letendre <James.Letendre@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author S. Cat <33800996+sparrowjack63@users.noreply.github.com>
 * @author Stephen Cuppett <steve@cuppett.com>
 * @author Jasper Weyne <jasperweyne@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Files\ObjectStore;

use Aws\ClientResolver;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Exception\CredentialsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use OCP\ICertificateManager;
use Psr\Log\LoggerInterface;

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

	/** @var string */
	protected $proxy;

	/** @var string */
	protected $storageClass;

	/** @var int */
	protected $uploadPartSize;

	/** @var int */
	private $putSizeLimit;

	protected $test;

	protected function parseParams($params) {
		if (empty($params['bucket'])) {
			throw new \Exception("Bucket has to be configured.");
		}

		$this->id = 'amazon::' . $params['bucket'];

		$this->test = isset($params['test']);
		$this->bucket = $params['bucket'];
		$this->proxy = $params['proxy'] ?? false;
		$this->timeout = $params['timeout'] ?? 15;
		$this->storageClass = !empty($params['storageClass']) ? $params['storageClass'] : 'STANDARD';
		$this->uploadPartSize = $params['uploadPartSize'] ?? 524288000;
		$this->putSizeLimit = $params['putSizeLimit'] ?? 104857600;
		$params['region'] = empty($params['region']) ? 'eu-west-1' : $params['region'];
		$params['hostname'] = empty($params['hostname']) ? 's3.' . $params['region'] . '.amazonaws.com' : $params['hostname'];
		if (!isset($params['port']) || $params['port'] === '') {
			$params['port'] = (isset($params['use_ssl']) && $params['use_ssl'] === false) ? 80 : 443;
		}
		$params['verify_bucket_exists'] = empty($params['verify_bucket_exists']) ? true : $params['verify_bucket_exists'];
		$this->params = $params;
	}

	public function getBucket() {
		return $this->bucket;
	}

	public function getProxy() {
		return $this->proxy;
	}

	/**
	 * Returns the connection
	 *
	 * @return S3Client connected client
	 * @throws \Exception if connection could not be made
	 */
	public function getConnection() {
		if (!is_null($this->connection)) {
			return $this->connection;
		}

		$scheme = (isset($this->params['use_ssl']) && $this->params['use_ssl'] === false) ? 'http' : 'https';
		$base_url = $scheme . '://' . $this->params['hostname'] . ':' . $this->params['port'] . '/';

		// Adding explicit credential provider to the beginning chain.
		// Including default credential provider (skipping AWS shared config files).
		$provider = CredentialProvider::memoize(
			CredentialProvider::chain(
				$this->paramCredentialProvider(),
				CredentialProvider::defaultProvider(['use_aws_shared_config_files' => false])
			)
		);

		$options = [
			'version' => isset($this->params['version']) ? $this->params['version'] : 'latest',
			'credentials' => $provider,
			'endpoint' => $base_url,
			'region' => $this->params['region'],
			'use_path_style_endpoint' => isset($this->params['use_path_style']) ? $this->params['use_path_style'] : false,
			'signature_provider' => \Aws\or_chain([self::class, 'legacySignatureProvider'], ClientResolver::_default_signature_provider()),
			'csm' => false,
			'use_arn_region' => false,
			'http' => ['verify' => $this->getCertificateBundlePath()],
			'use_aws_shared_config_files' => false,
		];
		if ($this->getProxy()) {
			$options['http']['proxy'] = $this->getProxy();
		}
		if (isset($this->params['legacy_auth']) && $this->params['legacy_auth']) {
			$options['signature_version'] = 'v2';
		}
		$this->connection = new S3Client($options);

		if (!$this->connection::isBucketDnsCompatible($this->bucket)) {
			$logger = \OC::$server->get(LoggerInterface::class);
			$logger->debug('Bucket "' . $this->bucket . '" This bucket name is not dns compatible, it may contain invalid characters.',
				['app' => 'objectstore']);
		}

		if ($this->params['verify_bucket_exists'] && !$this->connection->doesBucketExist($this->bucket)) {
			$logger = \OC::$server->get(LoggerInterface::class);
			try {
				$logger->info('Bucket "' . $this->bucket . '" does not exist - creating it.', ['app' => 'objectstore']);
				if (!$this->connection::isBucketDnsCompatible($this->bucket)) {
					throw new \Exception("The bucket will not be created because the name is not dns compatible, please correct it: " . $this->bucket);
				}
				$this->connection->createBucket(['Bucket' => $this->bucket]);
				$this->testTimeout();
			} catch (S3Exception $e) {
				$logger->debug('Invalid remote storage.', [
					'exception' => $e,
					'app' => 'objectstore',
				]);
				throw new \Exception('Creation of bucket "' . $this->bucket . '" failed. ' . $e->getMessage());
			}
		}

		// google cloud's s3 compatibility doesn't like the EncodingType parameter
		if (strpos($base_url, 'storage.googleapis.com')) {
			$this->connection->getHandlerList()->remove('s3.auto_encode');
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

	public static function legacySignatureProvider($version, $service, $region) {
		switch ($version) {
			case 'v2':
			case 's3':
				return new S3Signature();
			default:
				return null;
		}
	}

	/**
	 * This function creates a credential provider based on user parameter file
	 */
	protected function paramCredentialProvider(): callable {
		return function () {
			$key = empty($this->params['key']) ? null : $this->params['key'];
			$secret = empty($this->params['secret']) ? null : $this->params['secret'];

			if ($key && $secret) {
				return Promise\promise_for(
					new Credentials($key, $secret)
				);
			}

			$msg = 'Could not find parameters set for credentials in config file.';
			return new RejectedPromise(new CredentialsException($msg));
		};
	}

	protected function getCertificateBundlePath(): ?string {
		if ((int)($this->params['use_nextcloud_bundle'] ?? "0")) {
			// since we store the certificate bundles on the primary storage, we can't get the bundle while setting up the primary storage
			if (!isset($this->params['primary_storage'])) {
				/** @var ICertificateManager $certManager */
				$certManager = \OC::$server->get(ICertificateManager::class);
				return $certManager->getAbsoluteBundlePath();
			} else {
				return \OC::$SERVERROOT . '/resources/config/ca-bundle.crt';
			}
		} else {
			return null;
		}
	}

	protected function getSSECKey(): ?string {
		if (isset($this->params['sse_c_key'])) {
			return $this->params['sse_c_key'];
		}

		return null;
	}

	protected function getSSECParameters(bool $copy = false): array {
		$key = $this->getSSECKey();

		if ($key === null) {
			return [];
		}

		$rawKey = base64_decode($key);
		if ($copy) {
			return [
				'CopySourceSSECustomerAlgorithm' => 'AES256',
				'CopySourceSSECustomerKey' => $rawKey,
				'CopySourceSSECustomerKeyMD5' => md5($rawKey, true)
			];
		}
		return [
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true)
		];
	}
}
