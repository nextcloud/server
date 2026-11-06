<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use GuzzleHttp\Exception\TransferException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use Psr\Http\Message\StreamInterface;

/**
 * Client for the operations of the Azure Blob service REST API used by the object store.
 *
 * @see https://learn.microsoft.com/rest/api/storageservices/blob-service-rest-api
 */
class AzureBlobClient {
	public const DEFAULT_API_VERSION = '2025-01-05';
	/** Maximum size of a single Put Blob or Put Block request */
	public const MAX_BLOCK_SIZE = 4000 * 1024 * 1024;
	private const RETRYABLE_STATUS = [408, 429, 500, 502, 503, 504];

	/** Scheme, host and port of the endpoint */
	private string $origin;
	/** Path of the endpoint, set for path-style endpoints like Azurite */
	private string $basePath;

	/**
	 * @param string $endpoint the blob service endpoint, e.g. https://account.blob.core.windows.net
	 * @param array<string, mixed> $requestOptions options added to every request, e.g. verify or timeouts
	 * @param int $retries number of retries for failed requests that may succeed when repeated
	 * @param int $retryDelay delay before the first retry in milliseconds, doubled for every further retry
	 */
	public function __construct(
		private IClient $client,
		private AzureSharedKeySigner $signer,
		private ITimeFactory $timeFactory,
		string $endpoint,
		private string $apiVersion = self::DEFAULT_API_VERSION,
		private array $requestOptions = [],
		private int $retries = 3,
		private int $retryDelay = 500,
	) {
		$parts = parse_url($endpoint);
		if (!isset($parts['scheme'], $parts['host'])) {
			throw new \InvalidArgumentException('Invalid Azure blob endpoint: ' . $endpoint);
		}
		$this->origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
		$this->basePath = rtrim($parts['path'] ?? '', '/');
	}

	/**
	 * @return bool false if the container already exists
	 * @throws AzureException
	 */
	public function createContainer(string $container): bool {
		try {
			$this->send('PUT', $this->getPath($container), ['restype' => 'container'], [], null, [201]);
			return true;
		} catch (AzureException $e) {
			if ($e->getStatusCode() === 409) {
				return false;
			}
			throw $e;
		}
	}

	/**
	 * Upload a block blob in a single request.
	 *
	 * @param array<string, string> $headers additional headers, e.g. x-ms-blob-content-type
	 * @throws AzureException
	 */
	public function putBlob(string $container, string $blob, StreamInterface $body, int $size, array $headers = []): void {
		$this->send('PUT', $this->getPath($container, $blob), [], $headers + [
			'Content-Length' => (string)$size,
			'x-ms-blob-type' => 'BlockBlob',
		], $body, [201]);
	}

	/**
	 * Stage a block that becomes part of the blob once committed by putBlockList().
	 *
	 * @param string $blockId base64 encoded block id, see blockId()
	 * @throws AzureException
	 */
	public function putBlock(string $container, string $blob, string $blockId, StreamInterface $body, int $size): void {
		$this->send('PUT', $this->getPath($container, $blob), ['comp' => 'block', 'blockid' => $blockId], [
			'Content-Length' => (string)$size,
		], $body, [201]);
	}

	/**
	 * Commit staged blocks as the content of the blob.
	 *
	 * @param list<string> $blockIds base64 encoded block ids in content order
	 * @param array<string, string> $headers additional headers, e.g. x-ms-blob-content-type
	 * @throws AzureException
	 */
	public function putBlockList(string $container, string $blob, array $blockIds, array $headers = []): void {
		$xml = '<?xml version="1.0" encoding="utf-8"?><BlockList>';
		foreach ($blockIds as $blockId) {
			$xml .= '<Latest>' . htmlspecialchars($blockId, ENT_XML1) . '</Latest>';
		}
		$xml .= '</BlockList>';

		$this->send('PUT', $this->getPath($container, $blob), ['comp' => 'blocklist'], $headers + [
			'Content-Length' => (string)strlen($xml),
			'Content-Type' => 'application/xml',
		], $xml, [201]);
	}

	/**
	 * @param string|null $range byte range, e.g. "bytes=0-"
	 * @return IResponse streamed response, the body is a resource
	 * @throws AzureException
	 */
	public function getBlob(string $container, string $blob, ?string $range = null): IResponse {
		$headers = $range === null ? [] : ['Range' => $range];
		return $this->send('GET', $this->getPath($container, $blob), [], $headers, null, [200, 206], true);
	}

	/**
	 * @return array<string, string> the response headers with lower case names
	 * @throws AzureException
	 */
	public function getBlobProperties(string $container, string $blob): array {
		$response = $this->send('HEAD', $this->getPath($container, $blob), [], [], null, [200]);
		$headers = [];
		foreach ($response->getHeaders() as $name => $values) {
			$headers[strtolower($name)] = $values[0] ?? '';
		}
		return $headers;
	}

	/**
	 * @throws AzureException
	 */
	public function deleteBlob(string $container, string $blob): void {
		$this->send('DELETE', $this->getPath($container, $blob), [], [], null, [202]);
	}

	/**
	 * Copy a blob within the container and wait until the copy is complete.
	 *
	 * @param int $timeout maximum time in seconds to wait for a pending copy
	 * @throws AzureException
	 */
	public function copyBlob(string $container, string $source, string $target, int $timeout = 3600): void {
		$response = $this->send('PUT', $this->getPath($container, $target), [], [
			'Content-Length' => '0',
			'x-ms-copy-source' => $this->origin . $this->getPath($container, $source),
		], null, [202]);

		$status = $response->getHeader('x-ms-copy-status');
		$copyId = $response->getHeader('x-ms-copy-id');
		$description = '';
		$deadline = $this->timeFactory->getTime() + $timeout;
		$attempt = 0;
		while ($status === 'pending') {
			if ($this->timeFactory->getTime() > $deadline) {
				throw new AzureException("Copy of $source to $target did not complete within $timeout seconds", 0, 'CopyTimeout');
			}
			$this->sleep(min($attempt++, 4));
			$properties = $this->getBlobProperties($container, $target);
			if (($properties['x-ms-copy-id'] ?? '') !== $copyId) {
				throw new AzureException("Copy of $source to $target was replaced by another operation", 0, 'CopyReplaced');
			}
			$status = $properties['x-ms-copy-status'] ?? '';
			$description = $properties['x-ms-copy-status-description'] ?? '';
		}

		if ($status !== 'success') {
			throw new AzureException("Copy of $source to $target failed with status $status: $description", 0, 'CopyFailed');
		}
	}

	/**
	 * @return string block id of the given length that is unique for the upload and index
	 */
	public static function blockId(string $uploadId, int $index): string {
		return base64_encode($uploadId . sprintf('%06d', $index));
	}

	/**
	 * @return string the URL-encoded path of the container or blob
	 */
	private function getPath(string $container, ?string $blob = null): string {
		$path = $this->basePath . '/' . rawurlencode($container);
		if ($blob !== null) {
			$path .= '/' . str_replace('%2F', '/', rawurlencode($blob));
		}
		return $path;
	}

	/**
	 * @param array<string, string> $query
	 * @param array<string, string> $headers
	 * @param list<int> $expectedStatus
	 * @throws AzureException
	 */
	private function send(
		string $method,
		string $path,
		array $query,
		array $headers,
		StreamInterface|string|null $body,
		array $expectedStatus,
		bool $stream = false,
	): IResponse {
		$url = $this->origin . $path;
		if ($query !== []) {
			$url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
		}

		for ($attempt = 0; ; $attempt++) {
			if ($body instanceof StreamInterface) {
				$body->rewind();
			}

			$requestHeaders = $headers + [
				'x-ms-date' => gmdate('D, d M Y H:i:s \G\M\T', $this->timeFactory->getTime()),
				'x-ms-version' => $this->apiVersion,
			];
			$requestHeaders['Authorization'] = $this->signer->getAuthorizationHeader($method, $path, $query, $requestHeaders);

			$options = [
				'headers' => $requestHeaders,
				'http_errors' => false,
				// The endpoint is configured by the admin and may be a private endpoint
				'nextcloud' => ['allow_local_address' => true],
			] + $this->requestOptions;
			if ($body !== null) {
				$options['body'] = $body;
			}
			if ($stream) {
				$options['stream'] = true;
				// Streaming uses the PHP stream handler of Guzzle, which only supports HTTP/1.x
				$options['version'] = '1.1';
			}

			try {
				$response = $this->client->request($method, $url, $options);
			} catch (TransferException $e) {
				if ($attempt < $this->retries) {
					$this->sleep($attempt);
					continue;
				}
				throw new AzureException("$method $path failed: " . $e->getMessage(), 0, '', $e);
			}

			$status = $response->getStatusCode();
			if (in_array($status, $expectedStatus, true)) {
				return $response;
			}

			$exception = $this->createException($method, $path, $response);
			if ($attempt < $this->retries && in_array($status, self::RETRYABLE_STATUS, true)) {
				$this->sleep($attempt);
				continue;
			}
			throw $exception;
		}
	}

	private function createException(string $method, string $path, IResponse $response): AzureException {
		$body = $response->getBody();
		if (is_resource($body)) {
			$content = stream_get_contents($body, 65536);
			fclose($body);
		} else {
			$content = $body;
		}

		$status = $response->getStatusCode();
		$errorCode = $response->getHeader('x-ms-error-code');
		$message = '';
		if (is_string($content) && $content !== '') {
			$useInternalErrors = libxml_use_internal_errors(true);
			$xml = simplexml_load_string(preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content);
			libxml_clear_errors();
			libxml_use_internal_errors($useInternalErrors);
			if ($xml !== false) {
				$errorCode = $errorCode !== '' ? $errorCode : (string)$xml->Code;
				$message = trim(explode("\n", (string)$xml->Message)[0]);
			}
		}

		return new AzureException(trim("$method $path failed with $status $errorCode: $message", ' :'), $status, $errorCode);
	}

	private function sleep(int $attempt): void {
		if ($this->retryDelay > 0) {
			usleep($this->retryDelay * 1000 * (2 ** $attempt));
		}
	}
}
