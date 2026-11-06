<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\ObjectStore;

use Aws\Result;

/**
 * @since 26.0.0
 */
interface IObjectStoreMultiPartUpload {
	/**
	 * @since 26.0.0
	 */
	public function initiateMultipartUpload(string $urn): string;

	/**
	 * @return Result|array{ETag: string} the ETag of the uploaded part
	 * @since 26.0.0
	 * @since 36.0.0 may return an array
	 */
	public function uploadMultipartPart(string $urn, string $uploadId, int $partId, $stream, $size): Result|array;

	/**
	 * @param list<array<string, mixed>> $result the parts returned by getMultipartUploads(), sorted by part number
	 * @return int the size of the completed object
	 * @since 26.0.0
	 */
	public function completeMultipartUpload(string $urn, string $uploadId, array $result): int;

	/**
	 * @since 26.0.0
	 */
	public function abortMultipartUpload(string $urn, string $uploadId): void;

	/**
	 * @return array<array<string, mixed>> the uploaded parts, each with at least PartNumber, ETag and Size
	 * @since 26.0.0
	 */
	public function getMultipartUploads(string $urn, string $uploadId): array;
}
