<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\S3;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Pure unit tests for the `sse*` objectstore argument parsing and resulting
 * S3 API parameters in S3ConnectionTrait::parseEncryptionParams() /
 * getServerSideEncryptionParameters(). Unlike S3SSEKMSTest, these do not
 * require a live S3 bucket: S3::__construct() only calls parseParams(),
 * it never opens a connection.
 */
class S3ServerSideEncryptionTest extends TestCase {
	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		// S3ConnectionTrait::$loggedEncryptionWarnings is a static, class-wide dedup cache
		// (intentionally: it survives across the many S3 instances a long-lived
		// request/worker constructs). Reset it so tests asserting a warning IS logged don't
		// depend on which other test using an identical message ran first in this process.
		$property = new \ReflectionProperty(S3::class, 'loggedEncryptionWarnings');
		$property->setValue(null, []);
	}

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

	/**
	 * @return array Parameters S3 would merge into a request that does not create an object
	 */
	private function getReadServerSideEncryptionParameters(S3 $s3, bool $copySource = false): array {
		$method = new \ReflectionMethod($s3, 'getReadServerSideEncryptionParameters');
		return $method->invoke($s3, $copySource);
	}

	/**
	 * @return string[] Warnings recorded by parseEncryptionParams(), not yet flushed to the logger
	 */
	private function getEncryptionWarnings(S3 $s3): array {
		$property = new \ReflectionProperty($s3, 'encryptionWarnings');
		return $property->getValue($s3);
	}

	public function testNoEncryptionByDefault(): void {
		$s3 = $this->makeS3([]);
		$this->assertSame([], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
	}

	public function testSseS3(): void {
		$s3 = $this->makeS3(['sse' => 'sse-s3']);
		$this->assertSame(['ServerSideEncryption' => 'AES256'], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
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

		// SSE-C is warned about in every form, including this explicit, non-deprecated one:
		// it is a supported mode, not merely a deprecated way to reach one.
		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('SSE-C', $warnings[0]);
	}

	public function testSseCWithoutKeyIsRejected(): void {
		// Unlike a merely redundant option, this cannot be resolved: silently falling back
		// to no encryption would defeat an explicit request for SSE-C.
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("no 'sse_c_key' is configured");
		$this->makeS3(['sse' => 'sse-c']);
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

		// Both the deprecated-key warning and the generic SSE-C warning apply.
		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(2, $warnings);
		$this->assertStringContainsString('sse_c_key', $warnings[0]);
		$this->assertStringContainsString('deprecated', $warnings[0]);
		$this->assertStringContainsString('SSE-C', $warnings[1]);
	}

	public function testSseKms(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms']);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
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
		$this->assertSame([], $this->getEncryptionWarnings($s3));
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
		$this->assertSame([], $this->getEncryptionWarnings($s3));
	}

	public function testSseKmsWithBucketKey(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms', 'sse_kms_bucket_key' => true]);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms',
			'BucketKeyEnabled' => true,
		], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
	}

	public function testSseKmsDsse(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key']);
		$this->assertSame([
			'ServerSideEncryption' => 'aws:kms:dsse',
			'SSEKMSKeyId' => 'arn:aws:kms:us-east-1:123456789012:key/test-key',
		], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
	}

	public function testSseKmsDsseBucketKeyIsWarnedAndIgnored(): void {
		// AWS documents S3 Bucket Keys as unsupported for DSSE-KMS, but verified against a
		// real bucket: S3 accepts BucketKeyEnabled alongside 'aws:kms:dsse' and simply does
		// not apply it, rather than rejecting the request. Matches every other redundant
		// 'sse_kms_*' option: warn and drop, don't block boot over something S3 itself tolerates.
		$s3 = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_bucket_key' => true]);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms:dsse'], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('sse_kms_bucket_key', $warnings[0]);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataTruthyLegacyKmsEnabled')]
	public function testSseKmsLegacyAliasAcceptsAnyTruthyValue(mixed $value): void {
		// Truthy values such as 1 or 'true' from an environment-driven config must enable
		// encryption, not silently disable it.
		$s3 = $this->makeS3(['sse_kms_enabled' => $value]);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString("The 'sse_kms_enabled' objectstore argument is deprecated", $warnings[0]);
	}

	public static function dataTruthyLegacyKmsEnabled(): array {
		return [
			'bool true' => [true],
			'int 1' => [1],
			'string "true"' => ['true'],
			'string "1"' => ['1'],
		];
	}

	public function testBothLegacyKeysResolveToSseCByPrecedence(): void {
		// Matches the Nextcloud 34 behaviour: SSE-C silently took precedence over SSE-KMS
		// whenever both were configured. That precedence is kept, but is no longer silent.
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3(['sse_c_key' => base64_encode($rawKey), 'sse_kms_enabled' => true]);

		$this->assertSame([
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(3, $warnings);
		$this->assertStringContainsString("'sse_c_key' (SSE-C) takes precedence", $warnings[0]);
		$this->assertStringContainsString("The 'sse_c_key' objectstore argument is deprecated", $warnings[1]);
		$this->assertStringContainsString('SSE-C', $warnings[2]);
	}

	public function testExplicitSseKmsOverridesLegacySseCKey(): void {
		// An explicit 'sse' always wins, even over the deprecated key that would otherwise
		// take precedence under the Nextcloud 34 rule.
		$s3 = $this->makeS3([
			'sse' => 'sse-kms',
			'sse_c_key' => base64_encode(random_bytes(32)),
		]);

		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('sse_c_key', $warnings[0]);
		$this->assertStringContainsString('Ignoring', $warnings[0]);
	}

	public function testExplicitSseCOverridesLegacyKmsEnabled(): void {
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3([
			'sse' => 'sse-c',
			'sse_c_key' => base64_encode($rawKey),
			'sse_kms_enabled' => true,
		]);

		$this->assertSame([
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(2, $warnings);
		$this->assertStringContainsString("Ignoring the 'sse_kms_enabled' objectstore argument", $warnings[0]);
		$this->assertStringContainsString('SSE-C', $warnings[1]);
	}

	public function testInvalidSseValueIsRejected(): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage("Invalid 'sse'");
		$this->makeS3(['sse' => 'sse-does-not-exist']);
	}

	public function testKmsKeyIdWithoutKmsModeIsWarnedAndIgnored(): void {
		$s3 = $this->makeS3(['sse' => 'sse-s3', 'sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key']);
		$this->assertSame(['ServerSideEncryption' => 'AES256'], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('sse_kms_key_id', $warnings[0]);
	}

	public function testEncryptionContextWithoutKmsModeIsWarnedAndIgnored(): void {
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3([
			'sse' => 'sse-c',
			'sse_c_key' => base64_encode($rawKey),
			'sse_kms_encryption_context' => ['tenant' => 'acme'],
		]);

		$this->assertSame([
			'SSECustomerAlgorithm' => 'AES256',
			'SSECustomerKey' => $rawKey,
			'SSECustomerKeyMD5' => md5($rawKey, true),
		], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(2, $warnings);
		$this->assertStringContainsString('SSE-C', $warnings[0]);
		$this->assertStringContainsString("Ignoring the 'sse_kms_encryption_context' objectstore argument", $warnings[1]);
	}

	public static function dataInvalidEncryptionContext(): array {
		return [
			'non-string value' => [['tenant' => 123]],
			'list instead of map' => [['acme']],
			// PHP casts numeric-string array keys to int, so this is not a string key either
			'numeric-string key' => [['123' => 'acme']],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataInvalidEncryptionContext')]
	public function testEncryptionContextRejectsNonStringPairs(array $context): void {
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('must be an array of string key-value pairs');
		$this->makeS3(['sse' => 'sse-kms', 'sse_kms_encryption_context' => $context]);
	}

	public function testMalformedEncryptionContextIsRejectedRegardlessOfMode(): void {
		// A structurally invalid context can never be resolved, even for a mode where the
		// context itself would otherwise just be redundant and warned-about.
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('must be an array of string key-value pairs');
		$this->makeS3([
			'sse' => 'sse-c',
			'sse_c_key' => base64_encode(random_bytes(32)),
			'sse_kms_encryption_context' => 'not-an-array',
		]);
	}

	public static function dataKmsOnlyOptionsWithoutEncryption(): array {
		return [
			'sse_kms_key_id' => [['sse_kms_key_id' => 'arn:aws:kms:us-east-1:123456789012:key/test-key'], 'sse_kms_key_id'],
			'sse_kms_bucket_key' => [['sse_kms_bucket_key' => true], 'sse_kms_bucket_key'],
			'sse_kms_encryption_context' => [['sse_kms_encryption_context' => ['tenant' => 'acme']], 'sse_kms_encryption_context'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataKmsOnlyOptionsWithoutEncryption')]
	public function testKmsOnlyOptionWithoutAnyModeIsWarnedAndIgnored(array $arguments, string $option): void {
		$s3 = $this->makeS3($arguments);
		$this->assertSame([], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString("Ignoring the '$option' objectstore argument", $warnings[0]);
	}

	public function testEmptyKmsKeyIdMeansBucketDefaultKey(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms', 'sse_kms_key_id' => '']);
		$this->assertSame(['ServerSideEncryption' => 'aws:kms'], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getEncryptionWarnings($s3));
	}

	public function testBucketKeyWithoutSseKmsModeIsWarnedAndIgnored(): void {
		$s3 = $this->makeS3(['sse' => 'sse-s3', 'sse_kms_bucket_key' => true]);
		$this->assertSame(['ServerSideEncryption' => 'AES256'], $this->getServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(1, $warnings);
		$this->assertStringContainsString('sse_kms_bucket_key', $warnings[0]);
	}

	/**
	 * Unlike the other tests here, this exercises getConnection() (with verify_bucket_exists
	 * disabled, so it makes no network call) to prove parseEncryptionParams()'s warnings
	 * actually reach the logger.
	 */
	public function testWarningsAreLoggedViaGetConnection(): void {
		$rawKey = random_bytes(32);
		$s3 = $this->makeS3([
			'sse_c_key' => base64_encode($rawKey),
			'verify_bucket_exists' => false,
		]);

		$expectedWarnings = $this->getEncryptionWarnings($s3);
		$this->assertNotEmpty($expectedWarnings);

		$loggedMessages = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')
			->willReturnCallback(function (string $message, array $context = []) use (&$loggedMessages): void {
				$loggedMessages[] = $message;
			});
		$this->overwriteService(LoggerInterface::class, $logger);

		$s3->getConnection();

		$this->assertSame($expectedWarnings, $loggedMessages);
	}

	/**
	 * The dedup is keyed on message text and static across instances (a long-lived
	 * request/worker constructs the primary object storage repeatedly), so a second,
	 * distinct S3 instance with the same 'sse*' configuration must not log again.
	 */
	public function testIdenticalWarningsAreNotLoggedTwiceAcrossInstances(): void {
		$rawKey = random_bytes(32);
		$makeIdenticallyConfiguredS3 = fn (): S3 => $this->makeS3([
			'sse_c_key' => base64_encode($rawKey),
			'verify_bucket_exists' => false,
		]);

		$loggedMessages = [];
		$logger = $this->createMock(LoggerInterface::class);
		$logger->method('warning')
			->willReturnCallback(function (string $message, array $context = []) use (&$loggedMessages): void {
				$loggedMessages[] = $message;
			});
		$this->overwriteService(LoggerInterface::class, $logger);

		$first = $makeIdenticallyConfiguredS3();
		$expectedWarnings = $this->getEncryptionWarnings($first);
		$first->getConnection();
		$this->assertSame($expectedWarnings, $loggedMessages, 'First instance should log its warnings');

		$second = $makeIdenticallyConfiguredS3();
		$second->getConnection();
		$this->assertSame($expectedWarnings, $loggedMessages, 'Second instance with the same warnings should not log them again');

		$third = $this->makeS3(['sse_kms_enabled' => true, 'verify_bucket_exists' => false]);
		$thirdWarnings = $this->getEncryptionWarnings($third);
		$third->getConnection();
		$this->assertSame(array_merge($expectedWarnings, $thirdWarnings), $loggedMessages, 'An instance with different warnings should still log them');
	}

	public function testReadParametersIgnoreSseCKeyWhenAnotherModeIsExplicit(): void {
		$s3 = $this->makeS3(['sse' => 'sse-kms', 'sse_c_key' => base64_encode(random_bytes(32))]);
		$this->assertSame([], $this->getReadServerSideEncryptionParameters($s3));
	}

	public function testExplicitEmptySseOverridesLegacyAliases(): void {
		$s3 = $this->makeS3(['sse' => '', 'sse_c_key' => base64_encode(random_bytes(32)), 'sse_kms_enabled' => true]);

		$this->assertSame([], $this->getServerSideEncryptionParameters($s3));
		$this->assertSame([], $this->getReadServerSideEncryptionParameters($s3));

		$warnings = $this->getEncryptionWarnings($s3);
		$this->assertCount(2, $warnings);
		$this->assertStringContainsString('sse_c_key', $warnings[0]);
		$this->assertStringContainsString('sse_kms_enabled', $warnings[1]);
	}
}
