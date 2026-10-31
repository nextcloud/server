<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

use Aws\Result;
use Exception;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Files\ObjectStore\IObjectStoreMultiPartUpload;
use OCP\Files\ObjectStore\IObjectStorePresignedUpload;

class S3 implements IObjectStore, IObjectStoreMultiPartUpload, IObjectStorePresignedUpload, IObjectStoreMetaData {
	/** S3's limits for one part of a multipart upload. */
	private const MAX_PART_NUMBER = 10000;
	private const MAX_PART_SIZE = 5 * 1024 * 1024 * 1024;

	use S3ConnectionTrait;
	use S3ObjectTrait;

	public function __construct(array $parameters) {
		$parameters['primary_storage'] = true;
		$this->parseParams($parameters);
	}

	/**
	 * @return string the container or bucket name where objects are stored
	 * @since 7.0.0
	 */
	#[\Override]
	public function getStorageId() {
		return $this->id;
	}

	#[\Override]
	public function initiateMultipartUpload(string $urn): string {
		$upload = $this->getConnection()->createMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		] + $this->getServerSideEncryptionParameters());
		$uploadId = $upload->get('UploadId');
		if ($uploadId === null) {
			throw new Exception('No upload id returned');
		}
		return (string)$uploadId;
	}

	#[\Override]
	public function uploadMultipartPart(string $urn, string $uploadId, int $partId, $stream, $size): Result {
		return $this->getConnection()->uploadPart([
			'Body' => $stream,
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'ContentLength' => $size,
			'PartNumber' => $partId,
			'UploadId' => $uploadId,
		] + $this->getServerSideEncryptionParameters());
	}

	#[\Override]
	public function getMultipartUploads(string $urn, string $uploadId): array {
		$parts = [];
		$isTruncated = true;
		$partNumberMarker = 0;

		while ($isTruncated) {
			$result = $this->getConnection()->listParts([
				'Bucket' => $this->bucket,
				'Key' => $urn,
				'UploadId' => $uploadId,
				'MaxParts' => 1000,
				'PartNumberMarker' => $partNumberMarker,
			] + $this->getServerSideEncryptionParameters());
			$parts = array_merge($parts, $result->get('Parts') ?? []);
			$isTruncated = $result->get('IsTruncated');
			$partNumberMarker = $result->get('NextPartNumberMarker');
		}

		return $parts;
	}

	#[\Override]
	public function completeMultipartUpload(string $urn, string $uploadId, array $result): int {
		$this->getConnection()->completeMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'UploadId' => $uploadId,
			'MultipartUpload' => ['Parts' => $result],
		] + $this->getServerSideEncryptionParameters());
		$stat = $this->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		] + $this->getServerSideEncryptionParameters());
		return (int)$stat->get('ContentLength');
	}

	#[\Override]
	public function abortMultipartUpload($urn, $uploadId): void {
		$this->getConnection()->abortMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'UploadId' => $uploadId,
		]);
	}

	#[\Override]
	public function canPreSignUploads(): bool {
		// A customer-provided key would have to be sent with every part, and
		// legacy (V2) signatures can't sign a checksum.
		return $this->getSSECKey() === null && empty($this->params['legacy_auth']);
	}

	#[\Override]
	public function initiatePreSignedMultipartUpload(string $urn, ?string $mimetype = null): string {
		if (!$this->canPreSignUploads()) {
			throw new Exception('This object store configuration can\'t take presigned uploads');
		}
		$args = [
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'ChecksumAlgorithm' => 'SHA256',
			'StorageClass' => $this->storageClass,
		] + $this->getServerSideEncryptionParameters();
		if ($mimetype !== null && $mimetype !== '') {
			$args['ContentType'] = $mimetype;
		}
		$uploadId = $this->getConnection()->createMultipartUpload($args)->get('UploadId');
		if (!is_string($uploadId) || $uploadId === '') {
			throw new Exception('No upload id returned');
		}
		return $uploadId;
	}

	#[\Override]
	public function preSignUploadPart(string $urn, string $uploadId, int $partNumber, int $size, string $sha256, \DateTimeInterface $expiration): array {
		if ($partNumber < 1 || $partNumber > self::MAX_PART_NUMBER) {
			throw new \InvalidArgumentException('The part number must be between 1 and ' . self::MAX_PART_NUMBER);
		}
		if ($size < 0 || $size > self::MAX_PART_SIZE) {
			throw new \InvalidArgumentException('A part has at most ' . self::MAX_PART_SIZE . ' bytes');
		}
		if (preg_match('/^[0-9a-f]{64}$/', $sha256) !== 1) {
			throw new \InvalidArgumentException('The SHA-256 must be lowercase hex');
		}
		if (!$this->canPreSignUploads()) {
			throw new Exception('This object store configuration can\'t take presigned uploads');
		}
		$command = $this->getConnection()->getCommand('UploadPart', [
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'UploadId' => $uploadId,
			'PartNumber' => $partNumber,
			'ContentLength' => $size,
			'ChecksumSHA256' => base64_encode((string)hex2bin($sha256)),
		]);
		$request = $this->getConnection()->createPresignedRequest($command, $expiration);

		// The client has to send exactly the headers the signature covers;
		// its HTTP library sets the host from the URL.
		parse_str($request->getUri()->getQuery(), $query);
		$signed = is_string($query['X-Amz-SignedHeaders'] ?? null) ? explode(';', $query['X-Amz-SignedHeaders']) : [];
		$headers = [];
		foreach ($signed as $name) {
			if ($name !== 'host' && $request->hasHeader($name)) {
				$headers[$name] = $request->getHeaderLine($name);
			}
		}
		return ['method' => 'PUT', 'url' => (string)$request->getUri(), 'headers' => $headers];
	}

	#[\Override]
	public function getPreSignedUploadParts(string $urn, string $uploadId): array {
		$parts = [];
		foreach ($this->getMultipartUploads($urn, $uploadId) as $part) {
			$checksum = $part['ChecksumSHA256'] ?? null;
			$sha256 = null;
			if (is_string($checksum) && $checksum !== '') {
				$raw = base64_decode($checksum, true);
				$sha256 = ($raw !== false && strlen($raw) === 32) ? bin2hex($raw) : null;
			}
			$parts[] = [
				'number' => (int)$part['PartNumber'],
				'size' => (int)$part['Size'],
				'sha256' => $sha256,
			];
		}
		usort($parts, static fn (array $a, array $b): int => $a['number'] <=> $b['number']);
		return $parts;
	}

	private function parseS3Metadata(array $metadata): array {
		$result = [];
		foreach ($metadata as $key => $value) {
			if (str_starts_with($key, 'x-amz-meta-')) {
				if (str_starts_with($value, 'base64:')) {
					$value = base64_decode(substr($value, 7));
				}
				$result[substr($key, strlen('x-amz-meta-'))] = $value;
			}
		}
		return $result;
	}

	#[\Override]
	public function getObjectMetaData(string $urn): array {
		$object = $this->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn
		] + $this->getServerSideEncryptionParameters())->toArray();
		return [
			'mtime' => $object['LastModified'],
			'etag' => trim($object['ETag'], '"'),
			'size' => (int)($object['Size'] ?? $object['ContentLength']),
		] + $this->parseS3Metadata($object['Metadata'] ?? []);
	}

	#[\Override]
	public function listObjects(string $prefix = ''): \Iterator {
		$results = $this->getConnection()->getPaginator('ListObjectsV2', [
			'Bucket' => $this->bucket,
			'Prefix' => $prefix,
		] + $this->getServerSideEncryptionParameters());

		foreach ($results as $result) {
			if (is_array($result['Contents'])) {
				foreach ($result['Contents'] as $object) {
					yield [
						'urn' => basename($object['Key']),
						'metadata' => [
							'mtime' => $object['LastModified'],
							'etag' => trim($object['ETag'], '"'),
							'size' => (int)($object['Size'] ?? $object['ContentLength']),
						],
					];
				}
			}
		}
	}
}
