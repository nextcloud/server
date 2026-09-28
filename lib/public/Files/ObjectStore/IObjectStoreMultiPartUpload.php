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
	 * @since 26.0.0
	 */
	public function uploadMultipartPart(string $urn, string $uploadId, int $partId, $stream, $size): Result;

	/**
	 * @since 26.0.0
	 */
	public function completeMultipartUpload(string $urn, string $uploadId, array $result): int;

	/**
	 * @since 26.0.0
	 */
	public function abortMultipartUpload(string $urn, string $uploadId): void;

	/**
	 * @since 26.0.0
	 */
	public function getMultipartUploads(string $urn, string $uploadId): array;
}
