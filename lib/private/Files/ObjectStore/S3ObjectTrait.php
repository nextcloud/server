<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\S3\Exception\S3Exception;
use Aws\S3\Exception\S3MultipartUploadException;
use Aws\S3\MultipartCopy;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Utils;
use OC\Files\Stream\SeekableHttpStream;
use OCA\DAV\Connector\Sabre\Exception\BadGateway;
use OCP\Files\ObjectStore\ObjectAlreadyExistsException;
use OCP\ICacheFactory;
use OCP\Server;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

trait S3ObjectTrait {
	use S3ConfigTrait;

	/** Object key prefix used to probe whether the store enforces conditional writes. */
	private const CONDITIONAL_WRITE_PROBE_KEY = 'nextcloud-conditional-write-probe';
	/** How long a probe result is cached, in seconds. */
	private const CONDITIONAL_WRITE_PROBE_TTL = 604800; // 7 days

	/** Resolved conditional write support, memoized for the lifetime of this instance. */
	private ?bool $conditionalWritesSupported = null;

	/**
	 * Process-level memo of resolved probe results, keyed by "hostname::bucket", so a
	 * long-lived worker probes at most once even when no shared cache is configured.
	 * @var array<string, bool>
	 */
	private static array $conditionalWritesProbeCache = [];

	/**
	 * Returns the connection
	 *
	 * @return S3Client connected client
	 * @throws \Exception if connection could not be made
	 */
	abstract protected function getConnection();

	abstract protected function getCertificateBundlePath(): ?string;
	abstract protected function getSSECParameters(bool $copy = false): array;
	abstract protected function getServerSideEncryptionParameters(bool $copy = false): array;

	/**
	 * @param string $urn the unified resource name used to identify the object
	 *
	 * @return resource stream with the read data
	 * @throws \Exception when something goes wrong, message will be logged
	 * @since 7.0.0
	 */
	public function readObject($urn) {
		$fh = SeekableHttpStream::open(function ($range) use ($urn) {
			$command = $this->getConnection()->getCommand('GetObject', [
				'Bucket' => $this->bucket,
				'Key' => $urn,
				'Range' => 'bytes=' . $range,
			] + $this->getServerSideEncryptionParameters());
			$request = \Aws\serialize($command);
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
				]
			];
			$bundle = $this->getCertificateBundlePath();
			if ($bundle) {
				$opts['ssl'] = [
					'cafile' => $bundle
				];
			}

			if ($this->getProxy()) {
				$opts['http']['proxy'] = $this->getProxy();
				$opts['http']['request_fulluri'] = true;
			}

			$context = stream_context_create($opts);
			return fopen($request->getUri(), 'r', false, $context);
		});
		if (!$fh) {
			throw new \Exception("Failed to read object $urn");
		}
		return $fh;
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
	protected function writeSingle(string $urn, StreamInterface $stream, array $metaData, bool $ifNoneMatch = false): void {
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
		] + $this->getServerSideEncryptionParameters();

		if ($size = $stream->getSize()) {
			$args['ContentLength'] = $size;
		}

		if (!$ifNoneMatch) {
			$this->getConnection()->putObject($args);
			return;
		}

		// Refuse to overwrite an existing object. A concurrent delete may race the
		// write and yield a 409 Conflict; AWS allows retrying PutObject in that case,
		// but only when the body can be rewound to resend it intact.
		$args['IfNoneMatch'] = '*';
		$attempts = 0;
		while (true) {
			try {
				$this->getConnection()->putObject($args);
				return;
			} catch (S3Exception $e) {
				if ($this->isPreconditionFailed($e)) {
					throw new ObjectAlreadyExistsException($urn, previous: $e);
				}
				if ($this->isConditionalConflict($e) && $stream->isSeekable() && $attempts < $this->retriesMaxAttempts) {
					$attempts++;
					usleep(100 * 1000 * $attempts);
					$stream->rewind();
					continue;
				}
				throw $e;
			}
		}
	}

	/**
	 * Multipart upload helper that tries to avoid orphaned fragments in S3
	 *
	 * @param string $urn the unified resource name used to identify the object
	 * @param StreamInterface $stream stream with the data to write
	 * @param array $metaData the metadata to set for the object
	 * @throws \Exception when something goes wrong, message will be logged
	 */
	protected function writeMultiPart(string $urn, StreamInterface $stream, array $metaData, bool $ifNoneMatch = false): void {
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
		$preconditionFailed = false;

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
				] + $this->getServerSideEncryptionParameters(),
				'before_upload' => function (Command $command) use (&$totalWritten): void {
					$totalWritten += $command['ContentLength'];
				},
				'before_complete' => function (Command $command) use (&$totalWritten, $size, &$uploader, $ifNoneMatch): void {
					if ($size !== null && $totalWritten !== $size) {
						$e = new \Exception('Incomplete multi part upload, expected ' . $size . ' bytes, wrote ' . $totalWritten);
						throw new MultipartUploadException($uploader->getState(), $e);
					}
					// Refuse to overwrite an object that already exists. In-progress
					// multipart uploads are invisible to this check server-side, so it
					// only guards against a fully written object at the same key.
					if ($ifNoneMatch) {
						$command['IfNoneMatch'] = '*';
					}
				},
			]);

			try {
				$uploader->upload();
				$uploaded = true;
			} catch (S3MultipartUploadException $e) {
				$exception = $e;

				// A precondition failure means an object already exists at the key.
				// Retrying the completion cannot succeed, so stop and report it.
				if ($ifNoneMatch && $this->findPreconditionFailure($e)) {
					$preconditionFailed = true;
					break;
				}

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
				try {
					$this->getConnection()->abortMultipartUpload($uploadInfo);
				} catch (\Throwable $abortException) {
					// Best-effort cleanup: never let an abort failure mask the real error
					// reported below. Orphaned fragments should be reaped by a bucket
					// lifecycle rule for incomplete multipart uploads.
					Server::get(LoggerInterface::class)->debug(
						'Could not abort multipart upload after a failed write to "' . $urn . '"',
						['app' => 'objectstore', 'exception' => $abortException],
					);
				}
			}

			if ($preconditionFailed) {
				throw new ObjectAlreadyExistsException($urn, previous: $exception);
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
		$this->writeObjectWithCondition($urn, $stream, $metaData, false);
	}

	public function writeObjectIfNotExists(string $urn, $stream, array $metaData = []): void {
		$this->writeObjectWithCondition($urn, $stream, $metaData, true);
	}

	/**
	 * @param resource $stream
	 * @param bool $ifNoneMatch write only if no object exists at $urn yet
	 */
	private function writeObjectWithCondition(string $urn, $stream, array $metaData, bool $ifNoneMatch): void {
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
				$this->writeSingle($urn, $buffer, $metaData, $ifNoneMatch);
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

				$this->writeMultiPart($urn, $loadStream, $metaData, $ifNoneMatch);
			}
		} else {
			if ($size < $this->putSizeLimit) {
				$this->writeSingle($urn, $psrStream, $metaData, $ifNoneMatch);
			} else {
				$this->writeMultiPart($urn, $psrStream, $metaData, $ifNoneMatch);
			}
		}
		$psrStream->close();
	}

	public function supportsConditionalWrites(): bool {
		if ($this->conditionalWritesSupported !== null) {
			return $this->conditionalWritesSupported;
		}

		if ($this->conditionalWrites === false) {
			return $this->conditionalWritesSupported = false;
		}

		// Conditional writes require AWS Signature v4; the legacy v2 signer cannot sign them.
		if (!empty($this->params['legacy_auth'])) {
			Server::get(LoggerInterface::class)->warning(
				'Conditional writes disabled for S3 object store "' . $this->bucket . '": legacy (Signature v2) authentication cannot sign them.',
				['app' => 'objectstore'],
			);
			return $this->conditionalWritesSupported = false;
		}

		if ($this->conditionalWrites === true) {
			return $this->conditionalWritesSupported = true;
		}

		// 'auto': probe the bucket once and cache the outcome. A null result is a probe
		// failure (transient error, missing permissions, ...); it is memoized on this
		// instance but neither cached nor shared, so a persistently failing probe costs
		// one attempt per request instead of one per created file, and a later request
		// still retries it.
		return $this->conditionalWritesSupported = $this->probeConditionalWrites() ?? false;
	}

	/**
	 * Actively probe whether the store enforces the If-None-Match header.
	 *
	 * A store that silently ignores the header cannot be detected any other way,
	 * so we write a marker object twice: once unconditionally, then again with the
	 * condition. A compliant store rejects the second write with 412; a store that
	 * accepts it ignores the header and is treated as unsupported.
	 *
	 * @return bool|null true or false once determined, or null when the probe could
	 *                   not run (transient error) and should be retried later
	 */
	private function probeConditionalWrites(): ?bool {
		$cacheKey = $this->params['hostname'] . '::' . $this->bucket;

		// Process-level memo first: survives across requests in a long-lived worker even
		// when no shared cache is configured, so the probe runs at most once per worker.
		if (isset(self::$conditionalWritesProbeCache[$cacheKey])) {
			return self::$conditionalWritesProbeCache[$cacheKey];
		}

		$cache = Server::get(ICacheFactory::class)->createDistributed('s3-conditional-write-cache');
		$cached = $cache->get($cacheKey);
		if ($cached !== null) {
			return self::$conditionalWritesProbeCache[$cacheKey] = ($cached === 1);
		}

		$logger = Server::get(LoggerInterface::class);
		// A unique key per probe run so that two concurrent probes cannot delete each
		// other's marker and race to a wrong "ignores the header" conclusion.
		$probeKey = self::CONDITIONAL_WRITE_PROBE_KEY . '-' . bin2hex(random_bytes(8));
		$probeArgs = [
			'Bucket' => $this->bucket,
			'Key' => $probeKey,
			'Body' => '1',
			// Mirror the real single-object write so a bucket policy that mandates an
			// ACL or storage class does not reject the probe and wrongly disable the feature.
			'ACL' => 'private',
			'StorageClass' => $this->storageClass,
		] + $this->getServerSideEncryptionParameters();

		$supported = false;
		try {
			$connection = $this->getConnection();
			// Make sure the marker object exists so the conditional write has something to conflict with.
			$connection->putObject($probeArgs);

			try {
				$connection->putObject($probeArgs + ['IfNoneMatch' => '*']);
				// The store accepted a write that should have been rejected: it silently
				// ignores the header and would give a false sense of safety.
				$logger->warning(
					'S3 object store "' . $this->bucket . '" ignores the If-None-Match header; conditional writes disabled.',
					['app' => 'objectstore'],
				);
			} catch (S3Exception $e) {
				if ($this->isPreconditionFailed($e)) {
					$supported = true;
				} else {
					$logger->info(
						'S3 object store "' . $this->bucket . '" does not support conditional writes (' . ($e->getAwsErrorCode() ?: $e->getStatusCode()) . '); disabled.',
						['app' => 'objectstore'],
					);
				}
			}
		} catch (\Throwable $e) {
			// The probe itself failed (transient error, missing permissions, ...).
			// Return null so the result is neither cached nor memoized and can be retried.
			$logger->debug('Could not probe S3 conditional write support for "' . $this->bucket . '"', ['app' => 'objectstore', 'exception' => $e]);
			$this->deleteConditionalWriteProbe($probeKey);
			return null;
		}

		$this->deleteConditionalWriteProbe($probeKey);
		$cache->set($cacheKey, $supported ? 1 : 0, self::CONDITIONAL_WRITE_PROBE_TTL);
		return self::$conditionalWritesProbeCache[$cacheKey] = $supported;
	}

	private function deleteConditionalWriteProbe(string $probeKey): void {
		try {
			$this->getConnection()->deleteObject([
				'Bucket' => $this->bucket,
				'Key' => $probeKey,
			]);
		} catch (\Throwable $e) {
			// best-effort cleanup, a stray marker object is harmless
		}
	}

	private function isPreconditionFailed(AwsException $e): bool {
		return $e->getStatusCode() === 412 || $e->getAwsErrorCode() === 'PreconditionFailed';
	}

	private function isConditionalConflict(AwsException $e): bool {
		return $e->getStatusCode() === 409 || $e->getAwsErrorCode() === 'ConditionalRequestConflict';
	}

	private function findPreconditionFailure(\Throwable $e): bool {
		for ($cursor = $e; $cursor !== null; $cursor = $cursor->getPrevious()) {
			if ($cursor instanceof AwsException && $this->isPreconditionFailed($cursor)) {
				return true;
			}
		}
		return false;
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

	/**
	 * @throws S3Exception|\Exception if there is an unhandled exception
	 */
	public function objectExists($urn) {
		return $this->getConnection()->doesObjectExistV2($this->bucket, $urn, false, $this->getServerSideEncryptionParameters());
	}

	public function copyObject($from, $to, array $options = []) {
		$sourceMetadata = $this->getConnection()->headObject([
			'Bucket' => $this->getBucket(),
			'Key' => $from,
		] + $this->getServerSideEncryptionParameters());

		$size = (int)($sourceMetadata->get('Size') ?? $sourceMetadata->get('ContentLength'));

		if ($this->useMultipartCopy && $size > $this->copySizeLimit) {
			$copy = new MultipartCopy($this->getConnection(), [
				'source_bucket' => $this->getBucket(),
				'source_key' => $from
			], array_merge([
				'bucket' => $this->getBucket(),
				'key' => $to,
				'acl' => 'private',
				'params' => $this->getServerSideEncryptionParameters() + $this->getServerSideEncryptionParameters(true),
				'source_metadata' => $sourceMetadata
			], $options));
			$copy->copy();
		} else {
			$this->getConnection()->copy($this->getBucket(), $from, $this->getBucket(), $to, 'private', array_merge([
				'params' => $this->getServerSideEncryptionParameters() + $this->getServerSideEncryptionParameters(true),
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
