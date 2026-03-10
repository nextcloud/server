<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files\ObjectStore;

use Aws\Result;

/**
 * Multipart upload capabilities for object stores.
 *
 * Implementations are expected to support the standard multipart lifecycle:
 * initiate -> uploadMultipartPart (1..n) -> completeMultipartUpload | abortMultipartUpload.
 *
 * Notes:
 * - Part IDs are expected to be positive integers starting at 1.
 * - Callers should pass parts to completion in ascending part order unless an implementation documents otherwise.
 * - Re-uploading the same part ID for the same uploadId may overwrite the previously uploaded part,
 *   depending on backend semantics.
 *
 * @since 26.0.0
 */
interface IObjectStoreMultiPartUpload {
	/**
	 * Start a multipart upload for the object identified by $urn.
	 *
	 * @param string $urn Object identifier in the object store namespace.
	 * @return string Backend upload identifier to be used for subsequent part operations.
	 *
	 * @since 26.0.0
	 */
	public function initiateMultipartUpload(string $urn): string;

	/**
	 * Upload one multipart chunk for an active multipart upload.
	 *
	 * @param string $urn Object identifier in the object store namespace.
	 * @param string $uploadId Upload identifier previously returned by initiateMultipartUpload().
	 * @param int $partId Part number.
	 * @param resource|object $stream Stream payload for the part. Implementations may accept
	 * 								  stream resources or stream-like objects.
	 * @param int $size Size of the part payload in bytes.
	 * @return Result Backend result metadata for the uploaded part (e.g. ETag/checksum fields if provided).
	 *
	 * @since 26.0.0
	 */
	public function uploadMultipartPart(string $urn, string $uploadId, int $partId, $stream, $size): Result;

	/**
	 * Complete an active multipart upload by assembling uploaded parts.
	 *
	 * May take a long time!
	 *
	 * @param string $urn Object identifier in the object store namespace.
	 * @param string $uploadId Upload identifier previously returned by initiateMultipartUpload().
	 * @param array<int, array<string, mixed>> $result Part metadata used for final assembly.
	 *        Expected to contain backend-specific per-part information returned from uploadMultipartPart(),
	 *        commonly including part number and ETag/checksum fields.
	 * @return int Size in bytes of the assembled object as stored after upload completion.
	 *
	 * @since 26.0.0
	 */
	public function completeMultipartUpload(string $urn, string $uploadId, array $result): int;

	/**
	 * Abort an active multipart upload.
	 *
	 * After aborting, uploaded parts associated with the uploadId are expected to be discarded by backend
	 * cleanup semantics.
	 *
	 * @param string $urn Object identifier in the object store namespace.
	 * @param string $uploadId Upload identifier previously returned by initiateMultipartUpload().
	 *
	 * @since 26.0.0
	 */
	public function abortMultipartUpload(string $urn, string $uploadId): void;

	/**
	 * Retrieve already uploaded parts for a given multipart upload.
	 *
	 * @param string $urn Object identifier in the object store namespace.
	 * @param string $uploadId Upload identifier previously returned by initiateMultipartUpload().
	 * @return array<int, array<string, mixed>> Backend-specific list of uploaded part descriptors.
	 *
	 * @since 26.0.0
	 */
	public function getMultipartUploads(string $urn, string $uploadId): array;
}
