<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Files\ObjectStore;

use Aws\S3\Exception\S3MultipartUploadException;
use Aws\S3\MultipartCopy;
use Aws\S3\MultipartUploader;
use Aws\S3\S3Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Utils;
use OC\Files\Stream\SeekableHttpStream;
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
		$fh = SeekableHttpStream::open(function ($range) use ($urn) {
			$command = $this->getConnection()->getCommand('GetObject', [
				'Bucket' => $this->bucket,
				'Key' => $urn,
				'Range' => 'bytes=' . $range,
			] + $this->getSSECParameters());
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
			$result['x-amz-meta-' . $key] = $value;
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
		$this->getConnection()->putObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'Body' => $stream,
			'ACL' => 'private',
			'ContentType' => $mimetype,
			'Metadata' => $this->buildS3Metadata($metaData),
			'StorageClass' => $this->storageClass,
		] + $this->getSSECParameters());
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

		$attempts = 0;
		$uploaded = false;
		$concurrency = $this->concurrency;
		$exception = null;
		$state = null;

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
			}
		}

		if (!$uploaded) {
			// if anything goes wrong with multipart, make sure that you don´t poison and
			// slow down s3 bucket with orphaned fragments
			$uploadInfo = $exception->getState()->getId();
			if ($exception->getState()->isInitiated() && (array_key_exists('UploadId', $uploadInfo))) {
				$this->getConnection()->abortMultipartUpload($uploadInfo);
			}

			throw new \OCA\DAV\Connector\Sabre\Exception\BadGateway('Error while uploading to S3 bucket', 0, $exception);
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
		$psrStream = Utils::streamFor($stream);


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
				$loadStream = new Psr7\AppendStream([$buffer, $psrStream]);
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
}
