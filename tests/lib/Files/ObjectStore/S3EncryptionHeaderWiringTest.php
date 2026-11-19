<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Aws\CommandInterface;
use Aws\MockHandler;
use Aws\Result;
use Aws\S3\S3Client;
use OC\Files\ObjectStore\S3;
use Psr\Http\Message\RequestInterface;
use Test\TestCase;

/**
 * S3 whose connection is an AWS SDK client driven by a MockHandler, so every request the
 * object store would send can be inspected without a network.
 */
class MockedConnectionS3 extends S3 {
	private ?S3Client $mockedConnection = null;

	public function __construct(
		array $parameters,
		private MockHandler $handler,
	) {
		parent::__construct($parameters);
	}

	#[\Override]
	public function getConnection() {
		if ($this->mockedConnection === null) {
			$this->mockedConnection = new S3Client([
				'version' => 'latest',
				'region' => 'us-east-1',
				'credentials' => false,
				'endpoint' => 'https://s3.invalid',
				'use_path_style_endpoint' => true,
				'handler' => $this->handler,
			]);
		}
		return $this->mockedConnection;
	}
}

/**
 * Replacement for PHP's built-in https:// wrapper that records the request headers
 * S3ObjectTrait::readObject() hands to fopen() and then refuses to open, so nothing is
 * ever sent.
 */
class HeaderCapturingHttpWrapper {
	/** @var array<string, string> lower-cased header name => value, from the last open attempt */
	public static array $lastHeaders = [];

	/** @var resource */
	public $context;

	public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool {
		$lines = stream_context_get_options($this->context)['http']['header'] ?? [];
		self::$lastHeaders = [];
		foreach ($lines as $line) {
			[$name, $value] = array_map('trim', explode(':', $line, 2));
			self::$lastHeaders[strtolower($name)] = $value;
		}
		return false;
	}
}

/**
 * Pins the server-side encryption headers each S3 request actually carries on the wire, for
 * every `sse` mode: PutObject, CreateMultipartUpload and CopyObject carry the configured
 * encryption; requests that only touch an existing object carry nothing, except the SSE-C
 * customer key, which must accompany every request for an SSE-C object (on CopyObject in
 * its CopySource* variant, to decrypt the source).
 *
 * Assertions are made on serialized requests rather than on parameter arrays because the
 * AWS SDK only serializes members an operation defines, e.g. HeadObject has no
 * ServerSideEncryption member. Complements S3ServerSideEncryptionTest, which covers the
 * parameter builders.
 */
class S3EncryptionHeaderWiringTest extends TestCase {
	private const SSE_HEADERS = [
		'x-amz-server-side-encryption',
		'x-amz-server-side-encryption-aws-kms-key-id',
		'x-amz-server-side-encryption-context',
		'x-amz-server-side-encryption-bucket-key-enabled',
		'x-amz-server-side-encryption-customer-algorithm',
		'x-amz-server-side-encryption-customer-key',
		'x-amz-server-side-encryption-customer-key-md5',
		'x-amz-copy-source-server-side-encryption-customer-algorithm',
		'x-amz-copy-source-server-side-encryption-customer-key',
		'x-amz-copy-source-server-side-encryption-customer-key-md5',
	];

	private const KMS_KEY = 'arn:aws:kms:us-east-1:123456789012:key/test-key';

	private MockHandler $handler;

	/** @var list<array{0: string, 1: array<string, string>}> [operation name, lower-cased request headers] in send order */
	private array $sent = [];

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->handler = new MockHandler();
		$this->sent = [];
	}

	public static function dataModes(): array {
		return [
			'none' => [[]],
			'sse-s3' => [['sse' => 'sse-s3']],
			'sse-c' => [['sse' => 'sse-c', 'sse_c_key' => base64_encode(str_repeat('k', 32))]],
			'sse-kms' => [['sse' => 'sse-kms', 'sse_kms_key_id' => self::KMS_KEY, 'sse_kms_encryption_context' => ['tenant' => 'acme'], 'sse_kms_bucket_key' => true]],
			'sse-kms-dsse' => [['sse' => 'sse-kms-dsse', 'sse_kms_key_id' => self::KMS_KEY, 'sse_kms_encryption_context' => ['tenant' => 'acme']]],
		];
	}

	private function makeS3(array $arguments): MockedConnectionS3 {
		return new MockedConnectionS3(['bucket' => 'test-bucket'] + $arguments, $this->handler);
	}

	/**
	 * Queue one mocked response per expected request, recording what is actually sent.
	 */
	private function expectRequests(Result ...$results): void {
		foreach ($results as $result) {
			$this->handler->append(function (CommandInterface $command, RequestInterface $request) use ($result): Result {
				$headers = [];
				foreach ($request->getHeaders() as $name => $values) {
					$headers[strtolower($name)] = implode(',', $values);
				}
				$this->sent[] = [$command->getName(), $headers];
				return $result;
			});
		}
	}

	/**
	 * @return array<string, string> the SSE-related headers of the $index-th request sent
	 */
	private function sseHeadersOf(int $index, string $expectedOperation): array {
		$this->assertArrayHasKey($index, $this->sent, "Expected a request #$index ($expectedOperation) to have been sent");
		[$operation, $headers] = $this->sent[$index];
		$this->assertSame($expectedOperation, $operation, "Request #$index");
		return array_intersect_key($headers, array_flip(self::SSE_HEADERS));
	}

	/**
	 * The headers a request that creates an object must carry in the given mode.
	 *
	 * @return array<string, string>
	 */
	private static function expectedWriteHeaders(array $arguments): array {
		return match ($arguments['sse'] ?? '') {
			'' => [],
			'sse-s3' => ['x-amz-server-side-encryption' => 'AES256'],
			'sse-c' => self::expectedSseCHeaders($arguments, ''),
			'sse-kms' => [
				'x-amz-server-side-encryption' => 'aws:kms',
				'x-amz-server-side-encryption-aws-kms-key-id' => self::KMS_KEY,
				'x-amz-server-side-encryption-context' => base64_encode(json_encode(['tenant' => 'acme'])),
				'x-amz-server-side-encryption-bucket-key-enabled' => 'true',
			],
			'sse-kms-dsse' => [
				'x-amz-server-side-encryption' => 'aws:kms:dsse',
				'x-amz-server-side-encryption-aws-kms-key-id' => self::KMS_KEY,
				'x-amz-server-side-encryption-context' => base64_encode(json_encode(['tenant' => 'acme'])),
			],
		};
	}

	/**
	 * The headers a request that only reads an object must carry: nothing, except for SSE-C.
	 *
	 * @return array<string, string>
	 */
	private static function expectedReadHeaders(array $arguments): array {
		return ($arguments['sse'] ?? '') === 'sse-c' ? self::expectedSseCHeaders($arguments, '') : [];
	}

	/**
	 * @param string $prefix '' for the object itself, 'copy-source-' for the source of a copy
	 * @return array<string, string>
	 */
	private static function expectedSseCHeaders(array $arguments, string $prefix): array {
		$rawKey = base64_decode($arguments['sse_c_key']);
		return [
			"x-amz-{$prefix}server-side-encryption-customer-algorithm" => 'AES256',
			"x-amz-{$prefix}server-side-encryption-customer-key" => base64_encode($rawKey),
			"x-amz-{$prefix}server-side-encryption-customer-key-md5" => base64_encode(md5($rawKey, true)),
		];
	}

	private function stringToStream(string $data) {
		$stream = fopen('php://temp', 'w+');
		fwrite($stream, $data);
		rewind($stream);
		return $stream;
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testWriteObjectCarriesWriteHeaders(array $arguments): void {
		$s3 = $this->makeS3($arguments);
		$this->expectRequests(new Result([]));

		$s3->writeObject('urn', $this->stringToStream('data'));

		$this->assertSame(self::expectedWriteHeaders($arguments), $this->sseHeadersOf(0, 'PutObject'));
		$this->assertCount(1, $this->sent);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testReadRequestsCarryOnlyReadHeaders(array $arguments): void {
		$s3 = $this->makeS3($arguments);
		$read = self::expectedReadHeaders($arguments);

		$this->expectRequests(
			new Result(['ContentLength' => 4]), // HeadObject via objectExists()
			new Result(['ContentLength' => 4, 'LastModified' => new \DateTimeImmutable(), 'ETag' => '"e"']), // HeadObject via getObjectMetaData()
			new Result(['Parts' => [['PartNumber' => 1, 'ETag' => '"p1"']], 'IsTruncated' => false]), // ListParts
			new Result(['ETag' => '"p1"']), // UploadPart
			new Result([]), // CompleteMultipartUpload
			new Result(['ContentLength' => 4]), // HeadObject after CompleteMultipartUpload
		);

		$this->assertTrue($s3->objectExists('urn'));
		$this->assertSame($read, $this->sseHeadersOf(0, 'HeadObject'));

		$s3->getObjectMetaData('urn');
		$this->assertSame($read, $this->sseHeadersOf(1, 'HeadObject'));

		$s3->getMultipartUploads('urn', 'upload-id');
		$this->assertSame($read, $this->sseHeadersOf(2, 'ListParts'));

		$s3->uploadMultipartPart('urn', 'upload-id', 1, $this->stringToStream('data'), 4);
		$this->assertSame($read, $this->sseHeadersOf(3, 'UploadPart'));

		$s3->completeMultipartUpload('urn', 'upload-id', [['PartNumber' => 1, 'ETag' => '"p1"']]);
		$this->assertSame($read, $this->sseHeadersOf(4, 'CompleteMultipartUpload'));
		$this->assertSame($read, $this->sseHeadersOf(5, 'HeadObject'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testListObjectsCarriesNoEncryptionHeaders(array $arguments): void {
		// Listing is not an object operation: not even the SSE-C key belongs on it.
		$s3 = $this->makeS3($arguments);
		$this->expectRequests(new Result(['Contents' => [], 'IsTruncated' => false]));

		iterator_to_array($s3->listObjects('prefix/'), false);

		$this->assertSame([], $this->sseHeadersOf(0, 'ListObjectsV2'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testGetObjectCarriesOnlyReadHeaders(array $arguments): void {
		// readObject() does not go through the SDK handler: it serializes a GetObject command
		// and opens the resulting URL with fopen(), so the headers are captured there instead.
		$s3 = $this->makeS3($arguments);
		stream_wrapper_unregister('https');
		stream_wrapper_register('https', HeaderCapturingHttpWrapper::class);
		HeaderCapturingHttpWrapper::$lastHeaders = [];

		$failed = false;
		set_error_handler(static fn (): bool => true); // the refused fopen() emits a warning by design
		try {
			$s3->readObject('urn');
		} catch (\Exception $e) {
			$failed = str_contains($e->getMessage(), 'Failed to read object');
		} finally {
			restore_error_handler();
			stream_wrapper_restore('https');
		}
		$this->assertTrue($failed, 'readObject() should fail once the capturing wrapper refuses the connection');

		$headers = HeaderCapturingHttpWrapper::$lastHeaders;
		$this->assertSame('bytes=0-', $headers['range'] ?? null, 'GetObject was not attempted');
		$this->assertSame(self::expectedReadHeaders($arguments), array_intersect_key($headers, array_flip(self::SSE_HEADERS)));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testInitiateMultipartUploadCarriesWriteHeaders(array $arguments): void {
		$s3 = $this->makeS3($arguments);
		$this->expectRequests(new Result(['UploadId' => 'upload-id']));

		$this->assertSame('upload-id', $s3->initiateMultipartUpload('urn'));
		$this->assertSame(self::expectedWriteHeaders($arguments), $this->sseHeadersOf(0, 'CreateMultipartUpload'));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataModes')]
	public function testCopyObjectEncryptsDestinationAndDecryptsSource(array $arguments): void {
		$s3 = $this->makeS3($arguments);
		$read = self::expectedReadHeaders($arguments);

		$this->expectRequests(
			new Result(['ContentLength' => 4]), // HeadObject on the source, by copyObject() itself
			new Result(['ContentLength' => 4]), // HeadObject on the source, by the SDK's ObjectCopier
			new Result([]), // CopyObject
		);

		$s3->copyObject('from', 'to');

		$this->assertSame($read, $this->sseHeadersOf(0, 'HeadObject'));
		// The SDK's own HeadObject receives the full CopyObject parameter set
		$this->assertSame($read, $this->sseHeadersOf(1, 'HeadObject'));

		// Destination gets the write headers; for SSE-C the source key additionally travels in
		// the CopySource* variant so S3 can decrypt the source.
		$expectedCopy = self::expectedWriteHeaders($arguments);
		if (($arguments['sse'] ?? '') === 'sse-c') {
			$expectedCopy += self::expectedSseCHeaders($arguments, 'copy-source-');
		}
		$copyHeaders = $this->sseHeadersOf(2, 'CopyObject');
		ksort($expectedCopy);
		ksort($copyHeaders);
		$this->assertSame($expectedCopy, $copyHeaders);
	}
}
