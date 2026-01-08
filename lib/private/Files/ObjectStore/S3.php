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

class S3 implements IObjectStore, IObjectStoreMultiPartUpload, IObjectStoreMetaData {
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
	public function getStorageId() {
		return $this->id;
	}

	public function initiateMultipartUpload(string $urn): string {
		$upload = $this->getConnection()->createMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		] + $this->getSSECParameters());
		$uploadId = $upload->get('UploadId');
		if ($uploadId === null) {
			throw new Exception('No upload id returned');
		}
		return (string)$uploadId;
	}

	public function uploadMultipartPart(string $urn, string $uploadId, int $partId, $stream, $size): Result {
		return $this->getConnection()->uploadPart([
			'Body' => $stream,
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'ContentLength' => $size,
			'PartNumber' => $partId,
			'UploadId' => $uploadId,
		] + $this->getSSECParameters());
	}

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
			] + $this->getSSECParameters());
			$parts = array_merge($parts, $result->get('Parts') ?? []);
			$isTruncated = $result->get('IsTruncated');
			$partNumberMarker = $result->get('NextPartNumberMarker');
		}

		return $parts;
	}

	public function completeMultipartUpload(string $urn, string $uploadId, array $result): int {
		$this->getConnection()->completeMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'UploadId' => $uploadId,
			'MultipartUpload' => ['Parts' => $result],
		] + $this->getSSECParameters());
		$stat = $this->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		] + $this->getSSECParameters());
		return (int)$stat->get('ContentLength');
	}

	public function abortMultipartUpload($urn, $uploadId): void {
		$this->getConnection()->abortMultipartUpload([
			'Bucket' => $this->bucket,
			'Key' => $urn,
			'UploadId' => $uploadId,
		]);
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

	public function getObjectMetaData(string $urn): array {
		$object = $this->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn
		] + $this->getSSECParameters())->toArray();
		return [
			'mtime' => $object['LastModified'],
			'etag' => trim($object['ETag'], '"'),
			'size' => (int)($object['Size'] ?? $object['ContentLength']),
		] + $this->parseS3Metadata($object['Metadata'] ?? []);
	}

	public function listObjects(string $prefix = ''): \Iterator {
		$results = $this->getConnection()->getPaginator('ListObjectsV2', [
			'Bucket' => $this->bucket,
			'Prefix' => $prefix,
		] + $this->getSSECParameters());

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
