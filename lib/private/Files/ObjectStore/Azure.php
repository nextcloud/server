<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use GuzzleHttp\Psr7\Stream;
use Icewind\Streams\RetryWrapper;
use OC\Files\Stream\SeekableHttpStream;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\NotFoundException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\Server;

/**
 * Object store backed by an Azure Blob Storage container.
 *
 * Metadata names are stored with "_" instead of "-", as Azure only allows C# identifiers.
 *
 * @psalm-import-type ObjectMetaData from IObjectStoreMetaData
 *
 * Supported arguments:
 * - container, account_name, account_key: required
 * - endpoint: blob service endpoint, defaults to https://<account_name>.blob.core.windows.net
 * - autocreate: create the container if it does not exist
 * - upload_block_size: objects larger than this are uploaded in blocks of this size, defaults to 32 MiB
 * - api_version: Azure Storage service version, for environments that lag behind the public cloud
 * - connect_timeout, timeout: in seconds, timeout 0 waits indefinitely
 * - retries: number of retries for failed requests
 * - use_nextcloud_bundle: use the CA bundle managed by Nextcloud
 * - use_presigned_url: allow direct downloads from Azure with shared access signatures
 */
class Azure implements IObjectStore, IObjectStoreMetaData {
	public const DEFAULT_BLOCK_SIZE = 32 * 1024 * 1024;

	private string $containerName;
	private string $accountName;
	private string $accountKey;
	private string $endpoint;
	private bool $autoCreate;
	private int $blockSize;
	private string $apiVersion;
	private array $requestOptions;
	private int $retries;
	private bool $usePresignedUrl;

	public function __construct(
		array $parameters,
		private ?AzureBlobClient $blobClient = null,
	) {
		$this->containerName = $parameters['container'];
		$this->accountName = $parameters['account_name'];
		$this->accountKey = $parameters['account_key'];
		$this->endpoint = $parameters['endpoint'] ?? 'https://' . $this->accountName . '.blob.core.windows.net';
		$this->autoCreate = (bool)($parameters['autocreate'] ?? false);
		$this->blockSize = (int)($parameters['upload_block_size'] ?? self::DEFAULT_BLOCK_SIZE);
		if ($this->blockSize < 1 || $this->blockSize > AzureBlobClient::MAX_BLOCK_SIZE) {
			throw new \InvalidArgumentException('upload_block_size must be between 1 and ' . AzureBlobClient::MAX_BLOCK_SIZE . ' bytes');
		}
		$this->apiVersion = $parameters['api_version'] ?? AzureBlobClient::DEFAULT_API_VERSION;
		$this->requestOptions = [
			'connect_timeout' => $parameters['connect_timeout'] ?? 5,
			'timeout' => $parameters['timeout'] ?? 0,
			'verify' => CertificateBundle::getPath((bool)($parameters['use_nextcloud_bundle'] ?? false), true) ?? true,
		];
		$this->retries = (int)($parameters['retries'] ?? 3);
		$this->usePresignedUrl = (bool)($parameters['use_presigned_url'] ?? false);
	}

	private function getBlobClient(): AzureBlobClient {
		if ($this->blobClient === null) {
			$this->blobClient = new AzureBlobClient(
				Server::get(IClientService::class)->newClient(),
				new AzureSharedKeySigner($this->accountName, $this->accountKey),
				Server::get(ITimeFactory::class),
				$this->endpoint,
				$this->apiVersion,
				$this->requestOptions,
				$this->retries,
			);

			if ($this->autoCreate) {
				$this->createContainerIfNeeded($this->blobClient);
			}
		}
		return $this->blobClient;
	}

	private function createContainerIfNeeded(AzureBlobClient $client): void {
		$cache = Server::get(ICacheFactory::class)->createLocal('azure-container-exists');
		$cacheKey = $this->endpoint . '/' . $this->containerName;
		if ($cache->get($cacheKey) === true) {
			return;
		}
		$client->createContainer($this->containerName);
		$cache->set($cacheKey, true);
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 */
	#[\Override]
	public function getStorageId() {
		return 'azure::blob::' . $this->containerName;
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return resource stream with the read data
	 * @throws NotFoundException if the object does not exist
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	#[\Override]
	public function readObject($urn) {
		$client = $this->getBlobClient();
		$error = null;
		$handle = SeekableHttpStream::open(function (string $range) use ($client, $urn, &$error) {
			try {
				return $client->getBlob($this->containerName, $urn, 'bytes=' . $range);
			} catch (AzureException $e) {
				$error = $e;
				return false;
			}
		});

		if ($handle === false) {
			/** @var AzureException|null $error set by the callback */
			if ($error instanceof AzureException && $error->getStatusCode() === 404) {
				throw new NotFoundException("Object $urn not found in Azure container {$this->containerName}", 0, $error);
			}
			throw new \Exception("Failed to read object $urn", 0, $error);
		}
		return RetryWrapper::wrap($handle);
	}

	#[\Override]
	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$this->writeObjectWithMetaData($urn, $stream, $mimetype ? ['mimetype' => $mimetype] : []);
	}

	/**
	 * The size of the stream is never relied upon: uploads reach this method as
	 * php://input wrapped in other streams, which can neither seek nor report a size.
	 */
	#[\Override]
	public function writeObjectWithMetaData(string $urn, $stream, array $metaData): void {
		$client = $this->getBlobClient();
		$headers = $this->buildMetaDataHeaders($metaData);

		$uploadId = bin2hex(random_bytes(16));
		$blockIds = [];
		do {
			[$block, $size] = $this->readBlock($stream);
			try {
				if ($blockIds === [] && $size < $this->blockSize) {
					$client->putBlob($this->containerName, $urn, $block, $size, $headers);
					return;
				}
				if ($size > 0) {
					$blockId = AzureBlobClient::blockId($uploadId, count($blockIds));
					$client->putBlock($this->containerName, $urn, $blockId, $block, $size);
					$blockIds[] = $blockId;
				}
			} finally {
				$block->close();
			}
		} while ($size === $this->blockSize);

		$client->putBlockList($this->containerName, $urn, $blockIds, $headers);
	}

	/**
	 * @param array<string, mixed> $metaData
	 * @return array<string, string>
	 */
	private function buildMetaDataHeaders(array $metaData): array {
		$headers = [];
		foreach ($metaData as $key => $value) {
			if ($key === 'mimetype') {
				$headers['x-ms-blob-content-type'] = (string)$value;
			} elseif ($key !== 'size') {
				$value = (string)$value;
				if (preg_match('/^[\x20-\x7E]*$/', $value) !== 1 || trim($value) !== $value) {
					$value = 'base64:' . base64_encode($value);
				}
				$headers['x-ms-meta-' . preg_replace('/[^a-z0-9_]/', '_', strtolower($key))] = $value;
			}
		}
		return $headers;
	}

	/**
	 * @param array<string, string> $metadata metadata names without prefix and their values
	 * @return array<string, string>
	 */
	private function parseMetaData(array $metadata): array {
		$result = [];
		foreach ($metadata as $name => $value) {
			if (str_starts_with($value, 'base64:')) {
				$value = base64_decode(substr($value, 7));
			}
			$result[str_replace('_', '-', strtolower($name))] = $value;
		}
		return $result;
	}

	/**
	 * Read the next block of the stream into a temporary buffer.
	 *
	 * @param resource $stream
	 * @return array{Stream, int} the buffer and its size, smaller than the block size only at the end of the stream
	 */
	private function readBlock($stream): array {
		$buffer = fopen('php://temp', 'w+b');
		if ($buffer === false) {
			throw new \Exception('Failed to create a buffer for the upload');
		}
		$size = stream_copy_to_stream($stream, $buffer, $this->blockSize);
		if ($size === false) {
			fclose($buffer);
			throw new \Exception('Failed to read from the upload stream');
		}
		return [new Stream($buffer), $size];
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	#[\Override]
	public function deleteObject($urn) {
		$this->getBlobClient()->deleteBlob($this->containerName, $urn);
	}

	#[\Override]
	public function objectExists($urn) {
		try {
			$this->getBlobClient()->getBlobProperties($this->containerName, $urn);
			return true;
		} catch (AzureException $e) {
			if ($e->getStatusCode() === 404) {
				return false;
			}
			throw $e;
		}
	}

	#[\Override]
	public function copyObject($from, $to) {
		$this->getBlobClient()->copyBlob($this->containerName, $from, $to);
	}

	#[\Override]
	public function getObjectMetaData(string $urn): array {
		$properties = $this->getBlobClient()->getBlobProperties($this->containerName, $urn);

		$metadata = [];
		foreach ($properties as $name => $value) {
			if (str_starts_with($name, 'x-ms-meta-')) {
				$metadata[substr($name, strlen('x-ms-meta-'))] = $value;
			}
		}
		return $this->toObjectMetaData($properties, $metadata);
	}

	#[\Override]
	public function listObjects(string $prefix = ''): \Iterator {
		foreach ($this->getBlobClient()->listBlobs($this->containerName, $prefix) as $blob) {
			yield [
				'urn' => $blob['name'],
				'metadata' => $this->toObjectMetaData($blob['properties'], $blob['metadata']),
			];
		}
	}

	/**
	 * @param array<string, string> $properties blob properties with lower case names
	 * @param array<string, string> $metadata metadata names without prefix and their values
	 * @return ObjectMetaData
	 */
	private function toObjectMetaData(array $properties, array $metadata): array {
		/** @var ObjectMetaData */
		return $this->formatMetaData($properties) + $this->parseMetaData($metadata);
	}

	/**
	 * @param array<string, string> $properties blob properties with lower case names
	 * @return array{mtime?: \DateTime, etag?: string, size?: int, mimetype?: string}
	 */
	private function formatMetaData(array $properties): array {
		$result = [];
		if (isset($properties['last-modified'])) {
			$result['mtime'] = new \DateTime($properties['last-modified']);
		}
		if (isset($properties['etag'])) {
			$result['etag'] = trim($properties['etag'], '"');
		}
		if (isset($properties['content-length'])) {
			$result['size'] = (int)$properties['content-length'];
		}
		if (isset($properties['content-type'])) {
			$result['mimetype'] = $properties['content-type'];
		}
		return $result;
	}

	#[\Override]
	public function preSignedUrl(string $urn, \DateTimeInterface $expiration, ?string $filename = null): ?string {
		if (!$this->usePresignedUrl) {
			return null;
		}

		$client = $this->getBlobClient();
		if (!$client->supportsSas()) {
			return null;
		}
		return $client->getReadSasUrl($this->containerName, $urn, $expiration, ContentDisposition::attachment($filename));
	}
}
