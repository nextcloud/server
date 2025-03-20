<?php
/**
 * @copyright Copyright (c) 2016 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use Aws\Result;
use Exception;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Files\ObjectStore\IObjectStoreMultiPartUpload;

class S3 implements IObjectStore, IObjectStoreMultiPartUpload, IObjectStoreMetaData {
	use S3ConnectionTrait;
	use S3ObjectTrait;

	public function __construct($parameters) {
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

	public function getObjectMetaData(string $urn): array {
		$object = $this->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn
		] + $this->getSSECParameters())->toArray();
		return [
			'mtime' => $object['LastModified'],
			'etag' => trim($object['ETag'], '"'),
			'size' => (int)($object['Size'] ?? $object['ContentLength']),
		];
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
