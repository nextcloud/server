<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\ObjectStore;

/**
 * Server-side encryption mode for the S3 object store, configured via the
 * `sse` objectstore argument.
 */
enum S3EncryptionMode: string {
	/** No server-side encryption requested by Nextcloud (bucket defaults, if any, still apply) */
	case None = '';
	/** SSE-S3: S3-managed AES256 key */
	case SseS3 = 'sse-s3';
	/** SSE-C: customer-provided key, see `sse_c_key` */
	case SseC = 'sse-c';
	/** SSE-KMS: single layer of AWS KMS-managed encryption */
	case SseKms = 'sse-kms';
	/** DSSE-KMS: dual layer of AWS KMS-managed encryption */
	case SseKmsDsse = 'sse-kms-dsse';

	public function isKms(): bool {
		return $this === self::SseKms || $this === self::SseKmsDsse;
	}
}
