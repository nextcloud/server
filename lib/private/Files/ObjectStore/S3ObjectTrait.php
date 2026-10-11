<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use Aws\Command;
use Aws\Exception\AwsException;
use Aws\Exception\MultipartUploadException;
use Aws\Multipart\UploadState
use Aws\S3\Exception\S3Exception;
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
		] + $this->getServerSideEncryptionParameters();

		if ($size = $stream->getSize()) {
			$args['ContentLength'] = $size;
		}

		$this->getConnection()->putObject($args);
	}

	/**
	 * Uploads an object to S3 using multipart upload.
	 *
	 * @param string $urn the unified resource name used to identify the object
	 * @param StreamInterface $stream stream containing the data to upload
	 * @param array $metaData metadata to set on the object; `mimetype` is used as its content type
	 * @throws BadGateway if the upload fails or aborting a failed upload also fails
	 * @throws \Exception if setting up the multipart uploader fails
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
		$startPosition = $stream->isSeekable() ? $stream->tell() : null;
		$canResume = $startPosition === 0;
		$partLengths = [];

		// Retry part failures by resuming once at lower concurrency when the
		// source can be replayed from the beginning.
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
				'before_upload' => function (Command $command) use (&$partLengths): void {
					$partLengths[(int)$command['PartNumber']] = (int)$command['ContentLength'];
				},
				'before_complete' => function ($_command) use ($size, &$uploader, &$partLengths): void {
					if ($size !== null) {
						$this->validateMultipartUploadSize(
							$uploader->getState(),
							$size,
							$partLengths,
						);
					}
				},
			]);

			try {
				$uploader->upload();
				$uploaded = true;
			} catch (S3MultipartUploadException $e) {
				$exception = $e;
				$attempts++;

				// S3 may have completed the object even if the response failed.
				// Do not retry CompleteMultipartUpload errors.
				if ($this->isMultipartCompletionFailure($e)) {
					break;
				}

				// A non-seekable source cannot safely be resumed.
				if (!$canResume) {
					break;
				}

				// Reuse the upload ID and successful part records from this attempt.
				$state = $e->getState();

				if ($concurrency > 1) {
					$concurrency = round($concurrency / 2);
				}

				$stream->seek($startPosition);
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
					throw new BadGateway(
						'Error while uploading to S3 bucket; aborting the multipart upload also failed: '
							. $abortException->getMessage(),
						0,
						$exception,
					);
				}
			}

			throw new BadGateway('Error while uploading to S3 bucket', 0, $exception);
		}
	}

	private function isMultipartCompletionFailure(S3MultipartUploadException $exception): bool {
		for ($cause = $exception->getPrevious(); $cause !== null; $cause = $cause->getPrevious()) {
			if ($cause instanceof AwsException
				&& $cause->getCommand()->getName() === 'CompleteMultipartUpload'
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ensure a multipart upload contains all expected parts at their expected sizes.
	 *
	 * @param \Aws\Multipart\UploadState $state
	 * @param int|float $size
	 * @param array<int, int> $partLengths Content lengths submitted for each part
	 * @throws MultipartUploadException
	 */
	protected function validateMultipartUploadSize($state, $size, array $partLengths): void {
		$partSize = $state->getPartSize();
		$expectedPartCount = $size > 0 ? (int)ceil($size / $partSize) : 0;
		$expectedPartNumbers = $expectedPartCount > 0
			? range(1, $expectedPartCount)
			: [];
		$uploadedParts = $state->getUploadedParts();
		$uploadedPartNumbers = array_map('intval', array_keys($uploadedParts));

		$uploadedBytes = 0;
		$validLengths = true;
		foreach ($expectedPartNumbers as $partNumber) {
			if (!isset($uploadedParts[$partNumber])) {
				continue;
			}

			$expectedPartLength = min($partSize, $size - (($partNumber - 1) * $partSize));
			if (!isset($partLengths[$partNumber]) || $partLengths[$partNumber] !== $expectedPartLength) {
				$validLengths = false;
				continue;
			}

			$uploadedBytes += $partLengths[$partNumber];
		}

		if ($uploadedPartNumbers !== $expectedPartNumbers || !$validLengths || $uploadedBytes !== $size) {
			$e = new \Exception(
				'Incomplete multipart upload, expected ' . $size . ' bytes, wrote ' . $uploadedBytes
			);
			throw new MultipartUploadException($state, $e);
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
		if ($size !== null && $size >= $this->putSizeLimit) {
			// The known size already selects multipart, so don't first copy
			// up to the single-PUT limit into a temporary stream.
			$this->writeMultiPart($urn, $psrStream, $metaData);
		} elseif ($size === null || !$canSeek) {
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
			// The remaining case is a known-size, seekable stream below the limit.
			$this->writeSingle($urn, $psrStream, $metaData);
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
