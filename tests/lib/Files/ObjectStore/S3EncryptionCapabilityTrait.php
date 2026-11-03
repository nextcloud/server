<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Aws\S3\Exception\S3Exception;
use OC\Files\ObjectStore\S3;

/**
 * Detects which `ServerSideEncryption` values the configured S3 provider actually supports,
 * so live tests skip on providers without them (e.g. MinIO without KES, or anything other
 * than AWS for DSSE-KMS) instead of failing.
 *
 * Support is probed with a real PutObject + HeadObject rather than inferred from the
 * endpoint: the value counts as supported only if the provider accepts it and echoes it
 * back. Results are cached per test class.
 */
trait S3EncryptionCapabilityTrait {
	/** @var array<string, ?string> ServerSideEncryption value => null if supported, otherwise the reason it is not */
	private static array $encryptionSupport = [];

	/**
	 * @param S3 $s3 instance for the bucket under test; its own `sse*` configuration is not used
	 * @param string $serverSideEncryption 'AES256', 'aws:kms' or 'aws:kms:dsse'
	 * @param ?string $kmsKeyId KMS key for the 'aws:kms*' values, or null for the bucket default
	 * @return ?string null if supported, otherwise why not
	 */
	protected static function getEncryptionUnsupportedReason(S3 $s3, string $serverSideEncryption, ?string $kmsKeyId = null): ?string {
		if (array_key_exists($serverSideEncryption, self::$encryptionSupport)) {
			return self::$encryptionSupport[$serverSideEncryption];
		}

		$connection = $s3->getConnection();
		$urn = 'sse-capability-probe-' . bin2hex(random_bytes(8));
		$params = ['Bucket' => $s3->getBucket(), 'Key' => $urn, 'Body' => 'probe', 'ServerSideEncryption' => $serverSideEncryption];
		if ($kmsKeyId !== null && str_starts_with($serverSideEncryption, 'aws:kms')) {
			$params['SSEKMSKeyId'] = $kmsKeyId;
		}

		try {
			$connection->putObject($params);
			$echoed = $connection->headObject(['Bucket' => $s3->getBucket(), 'Key' => $urn])->get('ServerSideEncryption');
			$reason = $echoed === $serverSideEncryption
				? null
				: "the provider accepted ServerSideEncryption '$serverSideEncryption' but reports '" . ($echoed ?? 'none') . "'";
		} catch (S3Exception $e) {
			// 501 is how MinIO reports SSE without a configured KMS; any other 5xx is an outage
			$status = (int)$e->getStatusCode();
			if ($status >= 500 && $status !== 501) {
				throw $e;
			}
			$reason = "the provider rejected ServerSideEncryption '$serverSideEncryption' (HTTP $status " . $e->getAwsErrorCode() . ': ' . $e->getAwsErrorMessage() . ')';
		} finally {
			try {
				$connection->deleteObject(['Bucket' => $s3->getBucket(), 'Key' => $urn]);
			} catch (S3Exception) {
				// never written
			}
		}

		return self::$encryptionSupport[$serverSideEncryption] = $reason;
	}

	/**
	 * Skip the current test unless the provider supports $serverSideEncryption.
	 */
	protected static function requireEncryptionSupport(S3 $s3, string $serverSideEncryption, ?string $kmsKeyId = null): void {
		$reason = self::getEncryptionUnsupportedReason($s3, $serverSideEncryption, $kmsKeyId);
		if ($reason !== null) {
			self::markTestSkipped("S3 provider does not support '$serverSideEncryption': $reason");
		}
	}
}
