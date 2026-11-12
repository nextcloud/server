<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Aws\S3\Exception\S3Exception;
use OC\Files\ObjectStore\S3;
use OCP\IConfig;
use OCP\Server;
use Test\TestCase;

/**
 * Live permutation matrix for the S3 `sse*` encryption modes against a real AWS S3 bucket
 * and KMS key, including cross-mode reads (an object written under one mode, read back by
 * an instance configured for a different mode). Unlike S3SSEKMSTest, which is scoped to a
 * single mode taken from the configured primary objectstore, this constructs several S3
 * instances per test with different `sse*` overrides, so it cannot extend
 * ObjectStoreTestCase (one getInstance() per test class).
 *
 * Opt-in: skips unless NC_TEST_S3_KMS_KEY_ID names a real symmetric KMS key. Which modes
 * run is then decided by probing the provider (S3EncryptionCapabilityTrait): the whole class
 * skips without SSE-KMS support, and individual tests skip for SSE-S3 or DSSE-KMS when the
 * provider lacks those, as non-AWS providers such as MinIO and Ceph do for DSSE-KMS.
 */
#[\PHPUnit\Framework\Attributes\Group('PRIMARY-s3')]
#[\PHPUnit\Framework\Attributes\Group('S3-AWS-KMS')]
class S3EncryptionModesLiveTest extends TestCase {
	use S3EncryptionCapabilityTrait;

	private static array $baseArguments = [];
	private static string $kmsKeyId = '';

	/** @var string[] urns to delete in tearDown, across every instance created by makeS3() */
	private array $cleanupUrns = [];

	#[\Override]
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			self::markTestSkipped('S3 primary storage not configured');
		}

		$keyId = getenv('NC_TEST_S3_KMS_KEY_ID');
		if (!$keyId) {
			self::markTestSkipped('Set NC_TEST_S3_KMS_KEY_ID to a real AWS KMS key ARN to run the live sse* permutation matrix');
		}

		self::$baseArguments = $config['arguments'] ?? [];
		self::$kmsKeyId = $keyId;

		$reason = self::getEncryptionUnsupportedReason(self::makePlainS3(), 'aws:kms', self::$kmsKeyId);
		if ($reason !== null) {
			self::markTestSkipped("S3 provider does not support SSE-KMS with the configured key: $reason");
		}
	}

	#[\Override]
	protected function tearDown(): void {
		// Any instance from makeS3() can delete any urn: they all share one bucket.
		if ($this->cleanupUrns !== []) {
			$s3 = $this->makeS3([]);
			foreach ($this->cleanupUrns as $urn) {
				try {
					$s3->deleteObject($urn);
				} catch (\Exception) {
					// already gone, or never actually got written
				}
			}
		}

		parent::tearDown();
	}

	private function cleanupAfter(string $urn): string {
		$this->cleanupUrns[] = $urn;
		return $urn;
	}

	/**
	 * @param array $overrides 'sse*' (and other) arguments overriding the configured primary
	 *                         objectstore for this one instance; every other test in this
	 *                         file starts from a clean slate, with no 'sse*' carried over.
	 */
	private function makeS3(array $overrides): S3 {
		return new S3($overrides + self::getArgumentsWithoutEncryption());
	}

	private static function makePlainS3(): S3 {
		return new S3(self::getArgumentsWithoutEncryption());
	}

	private static function getArgumentsWithoutEncryption(): array {
		return array_filter(
			self::$baseArguments,
			static fn (string $key): bool => !str_starts_with($key, 'sse'),
			ARRAY_FILTER_USE_KEY,
		);
	}

	private function requireDsse(): void {
		self::requireEncryptionSupport(self::makePlainS3(), 'aws:kms:dsse', self::$kmsKeyId);
	}

	private function stringToStream(string $data) {
		$stream = fopen('php://temp', 'w+');
		fwrite($stream, $data);
		rewind($stream);
		return $stream;
	}

	private function headObject(S3 $s3, string $urn): array {
		return $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => $urn,
		])->toArray();
	}

	/**
	 * Some AWS accounts/buckets are configured to reject any upload that requests SSE-C
	 * outright ("this bucket has blocked upload requests that specify Server Side Encryption
	 * with Customer provided keys"), independent of anything Nextcloud does. That is a
	 * deliberate security control, not a bug to route around, so SSE-C tests skip on this
	 * specific error rather than failing -- and still run for real on a bucket without it.
	 */
	private function writeObjectOrSkipIfSseCBlocked(S3 $s3, string $urn, string $data): void {
		try {
			$s3->writeObject($urn, $this->stringToStream($data));
		} catch (S3Exception $e) {
			if ($e->getAwsErrorCode() === 'AccessDenied' && str_contains($e->getAwsErrorMessage() ?? '', 'Customer provided keys')) {
				self::markTestSkipped('This bucket/account blocks SSE-C uploads by policy: ' . $e->getAwsErrorMessage());
			}
			throw $e;
		}
	}

	/**
	 * Asserts that reading $urn throws, the way S3ObjectTrait::readObject() does for a GetObject
	 * error response (e.g. missing/wrong SSE-C key). Mirrors ObjectStoreTestCase::testReadNonExisting's
	 * pattern of tolerating the incidental fopen() PHP warning SeekableHttpStream raises alongside it.
	 */
	private function assertReadFails(S3 $s3, string $urn): void {
		$warnings = [];
		try {
			set_error_handler(function (int $errno, string $errstr) use (&$warnings): bool {
				if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
					return true;
				}
				$warnings[] = $errstr;
				return true;
			});
			stream_get_contents($s3->readObject($urn));
		} catch (\Exception) {
			foreach ($warnings as $warning) {
				$this->assertStringStartsWith('fopen(', $warning);
			}
			return;
		} finally {
			restore_error_handler();
		}
		$this->fail("Expected reading '$urn' to fail");
	}

	// --- Per-mode matrix -----------------------------------------------------------------

	public function testNoEncryption(): void {
		$urn = $this->cleanupAfter('live-sse-none');
		$s3 = $this->makeS3(['sse' => '']);
		$s3->writeObject($urn, $this->stringToStream('none'));

		// AWS applies its own bucket-default encryption (AES256) even when Nextcloud sends
		// no encryption headers at all; what matters here is that Nextcloud didn't ask for
		// KMS, not that the object ends up with literally no encryption.
		$head = $this->headObject($s3, $urn);
		$this->assertNotEquals('aws:kms', $head['ServerSideEncryption'] ?? null);
		$this->assertNotEquals('aws:kms:dsse', $head['ServerSideEncryption'] ?? null);
		$this->assertSame('none', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseS3(): void {
		self::requireEncryptionSupport(self::makePlainS3(), 'AES256');
		$urn = $this->cleanupAfter('live-sse-s3');
		$s3 = $this->makeS3(['sse' => 'sse-s3']);
		$s3->writeObject($urn, $this->stringToStream('sse-s3'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('AES256', $head['ServerSideEncryption']);
		$this->assertSame('sse-s3', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseC(): void {
		$urn = $this->cleanupAfter('live-sse-c');
		$key = base64_encode(random_bytes(32));
		$s3 = $this->makeS3(['sse' => 'sse-c', 'sse_c_key' => $key]);
		$this->writeObjectOrSkipIfSseCBlocked($s3, $urn, 'sse-c');

		$this->assertSame('sse-c', stream_get_contents($s3->readObject($urn)));

		// Genuinely differs from SSE-KMS/SSE-S3/none: reading without the customer key fails.
		$this->assertReadFails($this->makeS3(['sse' => '']), $urn);
	}

	public function testSseKmsBucketDefaultKey(): void {
		$urn = $this->cleanupAfter('live-sse-kms-default');
		$s3 = $this->makeS3(['sse' => 'sse-kms']);
		$s3->writeObject($urn, $this->stringToStream('sse-kms'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms', $head['ServerSideEncryption']);
		$this->assertSame('sse-kms', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseKmsWithKeyId(): void {
		$urn = $this->cleanupAfter('live-sse-kms-keyid');
		$s3 = $this->makeS3(['sse' => 'sse-kms', 'sse_kms_key_id' => self::$kmsKeyId]);
		$s3->writeObject($urn, $this->stringToStream('sse-kms-keyid'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms', $head['ServerSideEncryption']);
		$this->assertSame(self::$kmsKeyId, $head['SSEKMSKeyId']);
		$this->assertSame('sse-kms-keyid', stream_get_contents($s3->readObject($urn)));
	}

	public static function dataMultipartKmsModes(): array {
		return [
			'sse-kms' => ['sse-kms', 'aws:kms'],
			'sse-kms-dsse' => ['sse-kms-dsse', 'aws:kms:dsse'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataMultipartKmsModes')]
	public function testMultipartUpload(string $mode, string $expectedEncryption): void {
		self::requireEncryptionSupport(self::makePlainS3(), $expectedEncryption, self::$kmsKeyId);
		$urn = $this->cleanupAfter('live-multipart-' . $mode);
		$s3 = $this->makeS3(['sse' => $mode, 'sse_kms_key_id' => self::$kmsKeyId]);
		$data = random_bytes(1024);

		$uploadId = $s3->initiateMultipartUpload($urn);
		$s3->uploadMultipartPart($urn, $uploadId, 1, $this->stringToStream($data), strlen($data));
		$parts = $s3->getMultipartUploads($urn, $uploadId);
		$this->assertCount(1, $parts);
		$size = $s3->completeMultipartUpload($urn, $uploadId, [['ETag' => $parts[0]['ETag'], 'PartNumber' => 1]]);

		$this->assertSame(strlen($data), $size);
		$this->assertSame($expectedEncryption, $this->headObject($s3, $urn)['ServerSideEncryption']);
		$this->assertSame($data, stream_get_contents($s3->readObject($urn)));
	}

	public function testSseKmsWithEncryptionContext(): void {
		// Verified live: unlike ServerSideEncryption/SSEKMSKeyId, S3 does not echo the
		// encryption context back on HeadObject/GetObject -- it's write-only, used for KMS
		// authorization at PutObject/CopyObject/CreateMultipartUpload time. Confirming it was
		// actually applied would require reading the kms:EncryptionContext condition off a
		// CloudTrail Decrypt event, which is out of scope here; this just proves AWS accepts
		// the header (rather than erroring) and the object still round-trips normally.
		$urn = $this->cleanupAfter('live-sse-kms-context');
		$s3 = $this->makeS3([
			'sse' => 'sse-kms',
			'sse_kms_key_id' => self::$kmsKeyId,
			'sse_kms_encryption_context' => ['tenant' => 'acme'],
		]);
		$s3->writeObject($urn, $this->stringToStream('sse-kms-context'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms', $head['ServerSideEncryption']);
		$this->assertSame('sse-kms-context', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseKmsWithBucketKey(): void {
		$urn = $this->cleanupAfter('live-sse-kms-bucketkey');
		$s3 = $this->makeS3([
			'sse' => 'sse-kms',
			'sse_kms_key_id' => self::$kmsKeyId,
			'sse_kms_bucket_key' => true,
		]);
		$s3->writeObject($urn, $this->stringToStream('sse-kms-bucketkey'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms', $head['ServerSideEncryption']);
		$this->assertTrue($head['BucketKeyEnabled'] ?? false);
	}

	public function testSseKmsDsseWithKeyId(): void {
		$this->requireDsse();
		$urn = $this->cleanupAfter('live-sse-dsse-keyid');
		$s3 = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => self::$kmsKeyId]);
		$s3->writeObject($urn, $this->stringToStream('sse-dsse-keyid'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms:dsse', $head['ServerSideEncryption']);
		$this->assertSame(self::$kmsKeyId, $head['SSEKMSKeyId']);
		$this->assertSame('sse-dsse-keyid', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseKmsDsseWithEncryptionContext(): void {
		$this->requireDsse();
		// See testSseKmsWithEncryptionContext(): the context is not echoed back by S3.
		$urn = $this->cleanupAfter('live-sse-dsse-context');
		$s3 = $this->makeS3([
			'sse' => 'sse-kms-dsse',
			'sse_kms_key_id' => self::$kmsKeyId,
			'sse_kms_encryption_context' => ['tenant' => 'acme'],
		]);
		$s3->writeObject($urn, $this->stringToStream('sse-dsse-context'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms:dsse', $head['ServerSideEncryption']);
		$this->assertSame('sse-dsse-context', stream_get_contents($s3->readObject($urn)));
	}

	public function testSseKmsDsseBucketKeyIsIgnoredByAws(): void {
		$this->requireDsse();
		// Matches S3ServerSideEncryptionTest::testSseKmsDsseBucketKeyIsWarnedAndIgnored:
		// verified live that AWS accepts the request and simply doesn't apply the bucket
		// key, rather than rejecting it, despite documenting it as unsupported.
		$urn = $this->cleanupAfter('live-sse-dsse-bucketkey');
		$s3 = $this->makeS3([
			'sse' => 'sse-kms-dsse',
			'sse_kms_key_id' => self::$kmsKeyId,
			'sse_kms_bucket_key' => true,
		]);
		$s3->writeObject($urn, $this->stringToStream('sse-dsse-bucketkey'));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms:dsse', $head['ServerSideEncryption']);
		$this->assertFalse($head['BucketKeyEnabled'] ?? false);
	}

	// --- Cross-mode reads, the ones that catch real bugs ----------------------------------

	public function testDsseObjectReadableWithNoEncryptionHeaders(): void {
		$this->requireDsse();
		// Proves a DSSE-KMS object is readable by an instance with no encryption configured:
		// S3 decrypts KMS-encrypted objects without any key material from the client.
		$urn = $this->cleanupAfter('live-cross-dsse-write-plain-read');
		$writer = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => self::$kmsKeyId]);
		$writer->writeObject($urn, $this->stringToStream('cross-mode'));

		$reader = $this->makeS3(['sse' => '']);
		$this->assertTrue($reader->objectExists($urn));
		$this->assertSame('cross-mode', stream_get_contents($reader->readObject($urn)));
	}

	public function testSseKmsObjectReadableByDsseInstanceAndViceVersa(): void {
		$this->requireDsse();
		$kmsUrn = $this->cleanupAfter('live-cross-kms-write');
		$dsseUrn = $this->cleanupAfter('live-cross-dsse-write');

		$kms = $this->makeS3(['sse' => 'sse-kms', 'sse_kms_key_id' => self::$kmsKeyId]);
		$dsse = $this->makeS3(['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => self::$kmsKeyId]);

		$kms->writeObject($kmsUrn, $this->stringToStream('written-as-kms'));
		$dsse->writeObject($dsseUrn, $this->stringToStream('written-as-dsse'));

		$this->assertSame('written-as-kms', stream_get_contents($dsse->readObject($kmsUrn)));
		$this->assertSame('written-as-dsse', stream_get_contents($kms->readObject($dsseUrn)));
	}

	public function testMultipartUploadUnderDsse(): void {
		$this->requireDsse();
		// Parts inherit their encryption from CreateMultipartUpload, not from UploadPart;
		// this is the case that would catch a regression there. putSizeLimit is lowered so
		// a modest 6 MB body actually exercises the multipart path (default is 100 MB).
		$urn = $this->cleanupAfter('live-dsse-multipart');
		$s3 = $this->makeS3([
			'sse' => 'sse-kms-dsse',
			'sse_kms_key_id' => self::$kmsKeyId,
			'putSizeLimit' => 5 * 1024 * 1024,
		]);

		$data = str_repeat('M', 6 * 1024 * 1024);
		$s3->writeObject($urn, $this->stringToStream($data));

		$head = $this->headObject($s3, $urn);
		$this->assertSame('aws:kms:dsse', $head['ServerSideEncryption']);
		$this->assertSame($data, stream_get_contents($s3->readObject($urn)));
	}
}
