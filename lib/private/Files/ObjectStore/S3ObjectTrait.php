<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\Exception\S3MultipartUploadException;
use Aws\S3\MultipartCopy;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Utils;
use OC\Files\Stream\SeekableHttpStream;
use OCA\DAV\Connector\Sabre\Exception\BadGateway;
use Psr\Http\Message\StreamInterface;

trait S3ObjectTrait {
	use S3ConfigTrait;

	/**
	 * Returns the connection
	 *
	 * @return S3Client connected client
	 * @throws \Exception if connection could not be made
	 */
	abstract protected function getConnection();

	abstract protected function getCertificateBundlePath(): ?string;
	abstract protected function getSSECParameters(bool $copy = false): array;

	/**
	 * @param string $urn the unified resource name used to identify the object
	 *
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function readObject($urn) {
		$maxAttempts = max(1, $this->retriesMaxAttempts);
		$lastError = 'unknown error';
		$firstError = 'unknown error';

		// TODO: consider unifying logger access across S3ConnectionTrait and S3ObjectTrait
		// via an abstract method (e.g. getLogger()) rather than inline container lookups
		$logger = \OCP\Server::get(\Psr\Log\LoggerInterface::class);

		$fh = SeekableHttpStream::open(function ($range) use ($urn, $maxAttempts, &$lastError, $logger) {
			$command = $this->getConnection()->getCommand('GetObject', [
				'Bucket' => $this->bucket,
				'Key' => $urn,
				'Range' => 'bytes=' . $range,
			] + $this->getSSECParameters());

			$request = \Aws\serialize($command);
			$requestUri = (string)$request->getUri();

			$headers = [];
			foreach ($request->getHeaders() as $key => $values) {
				foreach ($values as $value) {
					$headers[] = "$key: $value";
				}
			}

			$opts = [
				'http' => [
					'protocol_version' => $request->getProtocolVersion(),
					'header' => $headers,
					'ignore_errors' => true,
				],
			];

			$bundle = $this->getCertificateBundlePath();
			if ($bundle) {
				$opts['ssl'] = [
					'cafile' => $bundle,
				];
			}

			if ($this->getProxy()) {
				$opts['http']['proxy'] = $this->getProxy();
				$opts['http']['request_fulluri'] = true;
			}

			$context = stream_context_create($opts);

			for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
				$result = @fopen($requestUri, 'r', false, $context);

				if ($result !== false) {
					$meta = stream_get_meta_data($result);
					$responseHead = $meta['wrapper_data'] ?? [];
					$statusCode = $this->parseHttpStatusCode($responseHead);

					if ($statusCode !== null && $statusCode < 400) {
						return $result;
					}

					$errorBody = stream_get_contents($result);
					fclose($result);

					$errorInfo = $this->parseS3ErrorResponse(
						$errorBody,
						is_array($responseHead) ? $responseHead : [$responseHead]
					);
					$currentError = $this->formatS3ReadError($urn, $range, $statusCode, $errorInfo, $attempt, $maxAttempts);
					// on retries, the last or the first failure can be most informative, but can't know which so track both
					if ($firstError === 'unknown error') {
						$firstError = $currentError;
					} else {
						$lastError = $currentError;
					}

					if ($this->isRetryableHttpStatus($statusCode) && $attempt < $maxAttempts) {
						// gives operators visibility into transient S3 issues even when retries succeed by logging
						$logger->warning($currentError, ['app' => 'objectstore']);
						$this->sleepBeforeRetry($attempt);
						continue;
					}

					// for non-retryable HTTP errors or exhausted retries, log the final failure with full S3 error context
					$logger->error($currentError, ['app' => 'objectstore']);
					return false;
				}

				// fopen returned false - i.e. connection-level failure (DNS, timeout, TLS, etc.)
				// log occurences for operator visibility even if retried
				$lastError = "connection failure while reading object $urn range $range on attempt $attempt/$maxAttempts (no HTTP response received)";
				$logger->warning($lastError, ['app' => 'objectstore']);

				if ($attempt < $maxAttempts) {
					$this->sleepBeforeRetry($attempt);
				}
			}

			return false;
		});

		if (!$fh) {
			throw new \Exception(
				"Failed to read object $urn after $maxAttempts attempts. First failure: $firstError. Last failure: $lastError"
			);
		}

		return $fh;
	}

	/**
	 * Parse the effective HTTP status code from stream wrapper metadata.
	 *
	 * wrapper_data can contain multiple status lines (e.g. 100 Continue,
	 * redirects, proxy responses). We want the last HTTP status line.
	 *
	 * @param array|string $responseHead The wrapper_data from stream_get_meta_data
	 */
	private function parseHttpStatusCode(array|string $responseHead): ?int {
		$lines = is_array($responseHead) ? $responseHead : [$responseHead];

		foreach (array_reverse($lines) as $line) {
			if (is_string($line) && preg_match('#^HTTP/\S+\s+(\d{3})#', $line, $matches)) {
				return (int)$matches[1];
			}
		}

		return null;
	}

	/**
	 * Parse S3 error response XML and response headers into a structured array.
	 *
	 * @param string|false $body The response body
	 * @param array $responseHead The wrapper_data from stream_get_meta_data
	 * @return array{code: string, message: string, requestId: string, extendedRequestId: string}
	 */
	private function parseS3ErrorResponse(string|false $body, array $responseHead): array {
		$errorCode = 'Unknown';
		$errorMessage = '';
		$requestId = '';
		$extendedRequestId = '';

		if ($body) {
			$xml = @simplexml_load_string($body);
			if ($xml !== false) {
				$errorCode = (string)($xml->Code ?? 'Unknown');
				$errorMessage = (string)($xml->Message ?? '');
				$requestId = (string)($xml->RequestId ?? '');
			}
		}

		foreach ($responseHead as $header) {
			if (!is_string($header)) {
				continue;
			}

			if (stripos($header, 'x-amz-request-id:') === 0) {
				$requestId = trim(substr($header, strlen('x-amz-request-id:')));
			} elseif (stripos($header, 'x-amz-id-2:') === 0) {
				$extendedRequestId = trim(substr($header, strlen('x-amz-id-2:')));
			}
		}

		return [
			'code' => $errorCode,
			'message' => $errorMessage,
			'requestId' => $requestId,
			'extendedRequestId' => $extendedRequestId,
		];
	}

	/**
	 * @param array{code: string, message: string, requestId: string, extendedRequestId: string} $errorInfo
	 */
	private function formatS3ReadError(
		string $urn,
		string $range,
		?int $statusCode,
		array $errorInfo,
		int $attempt,
		int $maxAttempts,
	): string {
		if ($statusCode === 416) {
			return sprintf(
				'HTTP 416 reading object %s range %s on attempt %d/%d: requested range not satisfiable',
				$urn,
				$range,
				$attempt,
				$maxAttempts,
				$errorInfo['code'],
				$errorInfo['message'],
				$errorInfo['requestId'],
				$errorInfo['extendedRequestId'],
			);
		}
		return sprintf(
			'HTTP %s reading object %s range %s on attempt %d/%d: %s - %s (RequestId: %s, ExtendedRequestId: %s)',
			$statusCode !== null ? (string)$statusCode : 'unknown',
			$urn,
			$range,
			$attempt,
			$maxAttempts,
			$errorInfo['code'],
			$errorInfo['message'],
			$errorInfo['requestId'],
			$errorInfo['extendedRequestId'],
		);
	}

	private function isRetryableHttpStatus(?int $statusCode): bool {
		return $statusCode === 429 || ($statusCode !== null && $statusCode >= 500);
	}

	private function sleepBeforeRetry(int $attempt): void {
		$delay = min(1000000, 100000 * (2 ** ($attempt - 1)));
		$delay += random_int(0, 100000);
		usleep($delay);
	}

	private function buildS3Metadata(array $metadata): array {
		$result = [];
		foreach ($metadata as $key => $value) {
			if (mb_check_encoding($value, 'ASCII')) {
				$result['x-amz-meta-' . $key] = $value;
			} else {
				$result['x-amz-meta-' . $key] = 'base64:' . base64_encode($value);
			}
		}
		return $result;
	}

	/**
	 * Single object put helper
	 *
	 * @param string $urn the unified resource name used to identify the object
	 * @param StreamInterface $stream stream with the data to write
	 * @param array $metaData the metadata to set for the object
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	protected function writeSingle(string $urn, StreamInterface $stream, array $metaData): void {
		$mimetype = $metaData['mimetype'] ?? null;
		unset($metaData['mimetype']);
		unset($metaData['size']);

		$args = [
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'Body' => $stream,
			'ACL' => 'private',
			'ContentType' => $mimetype,
			'Metadata' => $this->buildS3Metadata($metaData),
			'StorageClass' => $this->storageClass,
		] + $this->getSSECParameters();

		if ($size = $stream->getSize()) {
			$args['ContentLength'] = $size;
		}

		$this->getConnection()->putObject($args);
	}


	/**
	 * Multipart upload helper that tries to avoid orphaned fragments in S3
	 *
	 * @param string $urn the unified resource name used to identify the object
	 * @param StreamInterface $stream stream with the data to write
	 * @param array $metaData the metadata to set for the object
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	protected function writeMultiPart(string $urn, StreamInterface $stream, array $metaData): void {
		$mimetype = $metaData['mimetype'] ?? null;
		unset($metaData['mimetype']);
		unset($metaData['size']);

		$attempts = 0;
		$uploaded = false;
		$concurrency = $this->concurrency;
		$exception = null;
		$state = null;
		$size = $stream->getSize();
		$totalWritten = 0;

		// retry multipart upload once with concurrency at half on failure
		while (!$uploaded && $attempts <= 1) {
			$uploader = new MultipartUploader($this->getConnection(), $stream, [
				'bucket' => $this->bucket,
				'concurrency' => $concurrency,
				'key' => $urn,
				'part_size' => $this->uploadPartSize,
				'state' => $state,
				'params' => [
					'ContentType' => $mimetype,
					'Metadata' => $this->buildS3Metadata($metaData),
					'StorageClass' => $this->storageClass,
				] + $this->getSSECParameters(),
				'before_upload' => function (Command $command) use (&$totalWritten): void {
					$totalWritten += $command['ContentLength'];
				},
				'before_complete' => function ($_command) use (&$totalWritten, $size, &$uploader, &$attempts): void {
					if ($size !== null && $totalWritten != $size) {
						$e = new \Exception('Incomplete multi part upload, expected ' . $size . ' bytes, wrote ' . $totalWritten);
						throw new MultipartUploadException($uploader->getState(), $e);
					}
				},
			]);

			try {
				$uploader->upload();
				$uploaded = true;
			} catch (S3MultipartUploadException $e) {
				$exception = $e;
				$attempts++;

				if ($concurrency > 1) {
					$concurrency = round($concurrency / 2);
				}

				if ($stream->isSeekable()) {
					$stream->rewind();
				}
			} catch (MultipartUploadException $e) {
				$exception = $e;
				break;
			}
		}

		if (!$uploaded) {
			// if anything goes wrong with multipart, make sure that you don´t poison and
			// slow down s3 bucket with orphaned fragments
			$uploadInfo = $exception->getState()->getId();
			if ($exception->getState()->isInitiated() && (array_key_exists('UploadId', $uploadInfo))) {
				$this->getConnection()->abortMultipartUpload($uploadInfo);
			}

			throw new BadGateway('Error while uploading to S3 bucket', 0, $exception);
		}
	}

	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$metaData = [];
		if ($mimetype) {
			$metaData['mimetype'] = $mimetype;
		}
		$this->writeObjectWithMetaData($urn, $stream, $metaData);
	}

	public function writeObjectWithMetaData(string $urn, $stream, array $metaData): void {
		$canSeek = fseek($stream, 0, SEEK_CUR) === 0;
		$psrStream = Utils::streamFor($stream, [
			'size' => $metaData['size'] ?? null,
		]);


		$size = $psrStream->getSize();
		if ($size === null || !$canSeek) {
			// The s3 single-part upload requires the size to be known for the stream.
			// So for input streams that don't have a known size, we need to copy (part of)
			// the input into a temporary stream so the size can be determined
			$buffer = new Psr7\Stream(fopen('php://temp', 'rw+'));
			Utils::copyToStream($psrStream, $buffer, $this->putSizeLimit);
			$buffer->seek(0);
			if ($buffer->getSize() < $this->putSizeLimit) {
				// buffer is fully seekable, so use it directly for the small upload
				$this->writeSingle($urn, $buffer, $metaData);
			} else {
				if ($psrStream->isSeekable()) {
					// If the body is seekable, just rewind the body.
					$psrStream->rewind();
					$loadStream = $psrStream;
				} else {
					// If the body is non-seekable, stitch the rewind the buffer and
					// the partially read body together into one stream. This avoids
					// unnecessary disk usage and does not require seeking on the
					// original stream.
					$buffer->rewind();
					$loadStream = new Psr7\AppendStream([$buffer, $psrStream]);
				}

				$this->writeMultiPart($urn, $loadStream, $metaData);
			}
		} else {
			if ($size < $this->putSizeLimit) {
				$this->writeSingle($urn, $psrStream, $metaData);
			} else {
				$this->writeMultiPart($urn, $psrStream, $metaData);
			}
		}
		$psrStream->close();
	}

	/**
	 * @param string $urn the unified resource name used to identify the object
	 * @return void
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function deleteObject($urn) {
		$this->getConnection()->deleteObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		]);
	}

	public function objectExists($urn) {
		return $this->getConnection()->doesObjectExist($this->bucket, $urn, $this->getSSECParameters());
	}

	public function copyObject($from, $to, array $options = []) {
		$sourceMetadata = $this->getConnection()->headObject([
			'Bucket' => $this->getBucket(),
			'Key' => $from,
		] + $this->getSSECParameters());

		$size = (int)($sourceMetadata->get('Size') ?? $sourceMetadata->get('ContentLength'));

		if ($this->useMultipartCopy && $size > $this->copySizeLimit) {
			$copy = new MultipartCopy($this->getConnection(), [
				'source_bucket' => $this->getBucket(),
				'source_key' => $from
			], array_merge([
				'bucket' => $this->getBucket(),
				'key' => $to,
				'acl' => 'private',
				'params' => $this->getSSECParameters() + $this->getSSECParameters(true),
				'source_metadata' => $sourceMetadata
			], $options));
			$copy->copy();
		} else {
			$this->getConnection()->copy($this->getBucket(), $from, $this->getBucket(), $to, 'private', array_merge([
				'params' => $this->getSSECParameters() + $this->getSSECParameters(true),
				'mup_threshold' => PHP_INT_MAX,
			], $options));
		}
	}

	public function preSignedUrl(string $urn, \DateTimeInterface $expiration): ?string {
		if (!$this->isUsePresignedUrl()) {
			return null;
		}

		$command = $this->getConnection()->getCommand('GetObject', [
			'Bucket' => $this->getBucket(),
			'Key' => $urn,
		]);

		try {
			return (string)$this->getConnection()->createPresignedRequest($command, $expiration, [
				'signPayload' => true,
			])->getUri();
		} catch (AwsException) {
			return null;
		}
	}
}
