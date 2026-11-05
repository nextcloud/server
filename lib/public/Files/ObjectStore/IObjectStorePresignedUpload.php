<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Files\ObjectStore;

/**
 * An object store whose multipart uploads a client can feed directly: the
 * server starts the upload and hands out a presigned request per part, the
 * client sends the part's bytes straight to the store, and the server lists
 * and completes the upload with {@see IObjectStoreMultiPartUpload}. The
 * bytes never pass through the server, and the store's credentials never
 * leave it.
 *
 * Every part carries the SHA-256 it was presigned for; the store refuses a
 * part whose bytes don't match it.
 *
 * @since 36.0.0
 */
interface IObjectStorePresignedUpload extends IObjectStoreMultiPartUpload {
	/**
	 * Whether presigned uploads are possible with this store's
	 * configuration. They are not when a request would need a secret the
	 * client must not have, such as a customer-provided encryption key, or
	 * when the store's signature scheme can't sign a checksum.
	 *
	 * @since 36.0.0
	 */
	public function canPreSignUploads(): bool;

	/**
	 * Starts a multipart upload of $urn whose parts carry SHA-256 checksums.
	 * The object appears only when the upload is completed.
	 *
	 * @return string the upload's ID
	 * @throws \Exception when the store refuses
	 * @since 36.0.0
	 */
	public function initiatePreSignedMultipartUpload(string $urn, ?string $mimetype = null): string;

	/**
	 * A presigned request that uploads part $partNumber (1 to 10,000) of the
	 * upload $uploadId of $urn: exactly $size bytes whose SHA-256 is
	 * $sha256. The client sends the request with the given method and
	 * headers, unchanged, and the bytes as its body.
	 *
	 * The URL is a secret until it expires: whoever has it can upload this
	 * one part.
	 *
	 * @param string $sha256 lowercase hex SHA-256 of the part's bytes
	 * @return array{method: 'PUT', url: string, headers: array<string, string>}
	 * @throws \InvalidArgumentException for a part number, size or hash out of range
	 * @throws \Exception when the request can't be signed
	 * @since 36.0.0
	 */
	public function preSignUploadPart(string $urn, string $uploadId, int $partNumber, int $size, string $sha256, \DateTimeInterface $expiration): array;

	/**
	 * The parts the store holds for the upload, ordered by number, with the
	 * SHA-256 it checked (null when the store kept none).
	 *
	 * @return list<array{number: int, size: int, sha256: ?string}>
	 * @throws \Exception when the upload doesn't exist
	 * @since 36.0.0
	 */
	public function getPreSignedUploadParts(string $urn, string $uploadId): array;
}
