<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\S3;
use Test\TestCase;

/**
 * Pure unit tests for the `sse*` objectstore argument parsing and resulting
 * S3 API parameters in S3ConnectionTrait::parseEncryptionParams() /
 * getServerSideEncryptionParameters(). Unlike S3SSEKMSTest, these do not
 * require a live S3 bucket: S3::__construct() only calls parseParams(),
 * it never opens a connection.
 */
class S3ServerSideEncryptionTest extends TestCase {
	private function makeS3(array $arguments): S3 {
		return new S3(['bucket' => 'test-bucket'] + $arguments);
	}

	/**
	 * @return array Parameters S3 would merge into an S3 API call
	 */
	private function getServerSideEncryptionParameters(S3 $s3, bool $copy = false): array {
		$method = new \ReflectionMethod($s3, 'getServerSideEncryptionParameters');
		return $method->invoke($s3, $copy);
	}

	public function testNoEncryptionByDefault(): void {
		$s3 = $this->makeS3([]);
		$this->assertSame([], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseS3(): void {
		$s3 = $this->makeS3(['sse' => 'sse-s3']);
		$this->assertSame(['ServerSideEncryption' => 'AES256'], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseC(): void {
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3(['sse' => 'sse-c', 'sse_c_key' => base64_encode($rawKey)]);

		$this->assertSame([
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3));

		$this->assertSame([
			'CopySourceSSECustomerAlgorithm' => 'AES256',
			'CopySourceSSECustomerKey' => $rawKey,
			'CopySourceSSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3, true));
	}

	public function testSseCLegacyAliasWithoutExplicitSse(): void {
		// Configs from before the 'sse' argument existed must keep working unchanged.
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3(['sse_c_key' => base64_encode($rawKey)]);

		$this->assertSame([
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKms(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms']);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKmsWithKeyId(): void {
		$s3 = $this->makeS3([
			'sse' => 'sse-kms',
			'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
		]);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms',
			'SSEKMSKeyId' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
		], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKmsWithEncryptionContext(): void {
		$s3 = $this->makeS3([
			'sse' => 'sse-kms',
			'sse_kms_encryption_context' => ['tenant' => 'acme'],
		]);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms',
			// SSEKMSEncryptionContext is a plain string shape in the AWS SDK, so Nextcloud
			// must base64-encode the JSON itself; the SDK does not do this automatically.
			'SSEKMSEncryptionContext' => base64_encode(json_encode(['tenant' => 'acme'])),
		], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKmsWithBucketKey(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms', 'sse_kms_bucket_key' => true]);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms',
			'BucketKeyEnabled' => true,
		], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKmsDsse(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key']);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms:dsse',
			'SSEKMSKeyId' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
		], $this->getServerSideEncryptionParameters($s3));
	}

	public function testSseKmsDsseRejectsBucketKey(): void {
		// S3 Bucket Keys are not supported for DSSE-KMS.
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('not supported for DSSE-KMS');
		$this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_bucket_key' => true]);
	}

	/**
	 * @dataProvider dataTruthyLegacyKmsEnabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataTruthyLegacyKmsEnabled')]
	public function testSseKmsLegacyAliasAcceptsAnyTruthyValue(mixed $value): void {
		// Prior to this change, 'sse_kms_enabled' required a strict `=== true` check, so
		// e.g. 1 or 'true' from an environment-driven config would silently disable encryption.
		$s3 = $this->makeS3(['sse_kms_enabled' => $value]);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));
	}

	public static function dataTruthyLegacyKmsEnabled(): array {
		return [
			'bool true' => [true],
			'int 1' => [1],
			'string "true"' => ['true'],
			'string "1"' => ['1'],
		];
	}

	public function testConflictingLegacyKeysAreRejected(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('mutually exclusive');
		$this->makeS3(['sse_c_key' => base64_encode(random_bytes(32)), 'sse_kms_enabled' => true]);
	}

	public function testExplicitSseConflictingWithLegacySseCKeyIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-kms', 'sse_c_key' => base64_encode(random_bytes(32))]);
	}

	public function testExplicitSseConflictingWithLegacyKmsEnabledIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-c', 'sse_kms_enabled' => true]);
	}

	public function testInvalidSseValueIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("Invalid 'sse'");
		$this->makeS3(['sse' => 'sse-does-not-exist']);
	}

	public function testKmsKeyIdWithoutKmsModeIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-s3', 'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key']);
	}

	public function testEncryptionContextWithoutKmsModeIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-c', 'sse_c_key' => base64_encode(random_bytes(32)), 'sse_kms_encryption_context' => ['tenant' => 'acme']]);
	}

	public function testEncryptionContextRejectsNonStringValues(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-kms', 'sse_kms_encryption_context' => ['tenant' => 123]]);
	}

	public function testBucketKeyWithoutSseKmsModeIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->makeS3(['sse' => 'sse-s3', 'sse_kms_bucket_key' => true]);
	}
}
