<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use Aws\ClientResolver;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Exception\CredentialsException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\RejectedPromise;
use OCP\Files\StorageNotAvailableException;
use OCP\ICertificateManager;
use OCP\Server;
use Psr\Log\LoggerInterface;

trait S3ConnectionTrait {
	use S3ConfigTrait;

	protected string $id;

	protected bool $test;

	protected ?S3Client $connection = null;

	protected function parseParams($params) {
		if (empty($params['bucket'])) {
			throw new \Exception('Bucket has to be configured.');
		}

		$this->id = 'amazon::' . $params['bucket'];

		$this->test = isset($params['test']);
		$this->bucket = $params['bucket'];
		// Default to 5 like the S3 SDK does
		$this->concurrency = $params['concurrency'] ?? 5;
		$this->proxy = $params['proxy'] ?? false;
		$this->connectTimeout = $params['connect_timeout'] ?? 5;
		$this->timeout = $params['timeout'] ?? 15;
		$this->storageClass = !empty($params['storageClass']) ? $params['storageClass'] : 'STANDARD';
		$this->uploadPartSize = $params['uploadPartSize'] ?? 524288000;
		$this->putSizeLimit = $params['putSizeLimit'] ?? 104857600;
		$this->copySizeLimit = $params['copySizeLimit'] ?? 5242880000;
		$this->useMultipartCopy = (bool)($params['useMultipartCopy'] ?? true);
		$params['region'] = empty($params['region']) ? 'eu-west-1' : $params['region'];
		$params['hostname'] = empty($params['hostname']) ? 's3.' . $params['region'] . '.amazonaws.com' : $params['hostname'];
		$params['s3-accelerate'] = $params['hostname'] === 's3-accelerate.amazonaws.com' || $params['hostname'] === 's3-accelerate.dualstack.amazonaws.com';
		if (!isset($params['port']) || $params['port'] === '') {
			$params['port'] = (isset($params['use_ssl']) && $params['use_ssl'] === false) ? 80 : 443;
		}
		$params['verify_bucket_exists'] = $params['verify_bucket_exists'] ?? true;

		if ($params['s3-accelerate']) {
			$params['verify_bucket_exists'] = false;
		}

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
		if ($this->connection !== null) {
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
			'version' => $this->params['version'] ?? 'latest',
			'credentials' => $provider,
			'endpoint' => $base_url,
			'region' => $this->params['region'],
			'use_path_style_endpoint' => isset($this->params['use_path_style']) ? $this->params['use_path_style'] : false,
			'signature_provider' => \Aws\or_chain([self::class, 'legacySignatureProvider'], ClientResolver::_default_signature_provider()),
			'csm' => false,
			'use_arn_region' => false,
			'http' => [
				'verify' => $this->getCertificateBundlePath(),
				'connect_timeout' => $this->connectTimeout,
			],
			'use_aws_shared_config_files' => false,
			'retries' => [
				'mode' => 'standard',
				'max_attempts' => 5,
			],
		];

		if ($this->params['s3-accelerate']) {
			$options['use_accelerate_endpoint'] = true;
		} else {
			$options['endpoint'] = $base_url;
		}

		if ($this->getProxy()) {
			$options['http']['proxy'] = $this->getProxy();
		}
		if (isset($this->params['legacy_auth']) && $this->params['legacy_auth']) {
			$options['signature_version'] = 'v2';
		}
		$this->connection = new S3Client($options);

		try {
			$logger = Server::get(LoggerInterface::class);
			if (!$this->connection::isBucketDnsCompatible($this->bucket)) {
				$logger->debug('Bucket "' . $this->bucket . '" This bucket name is not dns compatible, it may contain invalid characters.',
					['app' => 'objectstore']);
			}

			if ($this->params['verify_bucket_exists'] && !$this->connection->doesBucketExist($this->bucket)) {
				try {
					$logger->info('Bucket "' . $this->bucket . '" does not exist - creating it.', ['app' => 'objectstore']);
					if (!$this->connection::isBucketDnsCompatible($this->bucket)) {
						throw new StorageNotAvailableException('The bucket will not be created because the name is not dns compatible, please correct it: ' . $this->bucket);
					}
					$this->connection->createBucket(['Bucket' => $this->bucket]);
					$this->testTimeout();
				} catch (S3Exception $e) {
					$logger->debug('Invalid remote storage.', [
						'exception' => $e,
						'app' => 'objectstore',
					]);
					if ($e->getAwsErrorCode() !== 'BucketAlreadyOwnedByYou') {
						throw new StorageNotAvailableException('Creation of bucket "' . $this->bucket . '" failed. ' . $e->getMessage());
					}
				}
			}

			// google cloud's s3 compatibility doesn't like the EncodingType parameter
			if (strpos($base_url, 'storage.googleapis.com')) {
				$this->connection->getHandlerList()->remove('s3.auto_encode');
			}
		} catch (S3Exception $e) {
			throw new StorageNotAvailableException('S3 service is unable to handle request: ' . $e->getMessage());
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
			$sessionToken = empty($this->params['session_token']) ? null : $this->params['session_token'];

			if ($key && $secret) {
				return Create::promiseFor(
					// a null sessionToken match the default signature of the constructor
					new Credentials($key, $secret, $sessionToken)
				);
			}

			$msg = 'Could not find parameters set for credentials in config file.';
			return new RejectedPromise(new CredentialsException($msg));
		};
	}

	protected function getCertificateBundlePath(): ?string {
		if ((int)($this->params['use_nextcloud_bundle'] ?? '0')) {
			// since we store the certificate bundles on the primary storage, we can't get the bundle while setting up the primary storage
			if (!isset($this->params['primary_storage'])) {
				/** @var ICertificateManager $certManager */
				$certManager = Server::get(ICertificateManager::class);
				return $certManager->getAbsoluteBundlePath();
			} else {
				return \OC::$SERVERROOT . '/resources/config/ca-bundle.crt';
			}
		} else {
			return null;
		}
	}

	protected function getSSECKey(): ?string {
		if (isset($this->params['sse_c_key']) && !empty($this->params['sse_c_key'])) {
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
