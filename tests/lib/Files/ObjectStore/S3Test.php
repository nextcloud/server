<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\Wrapper;
use OC\Files\ObjectStore\S3;
use OCP\IConfig;
use OCP\Server;

class MultiPartUploadS3 extends S3 {
	#[\Override]
	public function writeObject($urn, $stream, ?string $mimetype = null) {
		$this->getConnection()->upload($this->bucket, $urn, $stream, 'private', [
			'mup_threshold' => 1,
		]);
	}
}

class NonSeekableStream extends Wrapper {
	public static function wrap($source) {
		$context = stream_context_create([
			'nonseek' => [
				'source' => $source,
			],
		]);
		return Wrapper::wrapSource($source, $context, 'nonseek', self::class);
	}

	#[\Override]
	public function dir_opendir($path, $options) {
		return false;
	}

	#[\Override]
	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('nonseek');
		return true;
	}

	#[\Override]
	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}
}

#[\PHPUnit\Framework\Attributes\Group('PRIMARY-s3')]
class S3Test extends ObjectStoreTestCase {
	#[\Override]
	public function setUp(): void {
		parent::setUp();
		$s3 = $this->getInstance();
		$s3->deleteObject('multiparttest');
	}

	#[\Override]
	protected function getInstance() {
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			$this->markTestSkipped('objectstore not configured for s3');
		}

		return new S3($config['arguments']);
	}

	public function testUploadNonSeekable(): void {
		$this->cleanupAfter('multiparttest');

		$s3 = $this->getInstance();

		$s3->writeObject('multiparttest', NonSeekableStream::wrap(fopen(__FILE__, 'r')));

		$result = $s3->readObject('multiparttest');

		$this->assertEquals(file_get_contents(__FILE__), stream_get_contents($result));
	}

	public function testSeek(): void {
		$this->cleanupAfter('seek');

		$data = file_get_contents(__FILE__);

		$instance = $this->getInstance();
		$instance->writeObject('seek', $this->stringToStream($data));

		$read = $instance->readObject('seek');
		$this->assertEquals(substr($data, 0, 100), fread($read, 100));

		fseek($read, 10);
		$this->assertEquals(substr($data, 10, 100), fread($read, 100));

		fseek($read, 100, SEEK_CUR);
		$this->assertEquals(substr($data, 210, 100), fread($read, 100));
	}

	public function assertNoUpload($objectUrn) {
		/** @var S3 */
		$s3 = $this->getInstance();
		$s3client = $s3->getConnection();
		$uploads = $s3client->listMultipartUploads([
			'Bucket' => $s3->getBucket(),
			'Prefix' => $objectUrn,
		]);
		$this->assertArrayNotHasKey('Uploads', $uploads, 'Assert is not uploaded');
	}

	public function testEmptyUpload(): void {
		$s3 = $this->getInstance();

		$emptyStream = fopen('php://memory', 'r');
		fwrite($emptyStream, '');

		$warnings = [];
		set_error_handler(
			function (int $errno, string $errstr) use (&$warnings): void {
				if ($errno === E_DEPRECATED || $errno === E_USER_DEPRECATED) {
					return;
				}
				$warnings[] = $errstr;
			},
		);

		$s3->writeObject('emptystream', $emptyStream);

		$this->assertNoUpload('emptystream');
		$this->assertTrue($s3->objectExists('emptystream'), 'Object exists on S3');

		$thrown = false;
		try {
			self::assertFalse($s3->readObject('emptystream'), 'Reading empty stream object should return false');
		} catch (\Exception $e) {
			// An exception is expected here since 0 byte files are wrapped
			// to be read from an empty memory stream in the ObjectStoreStorage
			$thrown = true;
		}
		self::assertTrue($thrown, 'readObject with range requests are not expected to work on empty objects');

		$s3->deleteObject('emptystream');
		$this->assertOnlyExpectedWarnings($warnings);
		restore_error_handler();
	}

	/** File size to upload in bytes */
	public static function dataFileSizes(): array {
		return [
			[1000000], [2000000], [5242879], [5242880], [5242881], [10000000]
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataFileSizes')]
	public function testFileSizes($size): void {
		$this->cleanupAfter('testfilesizes');
		$s3 = $this->getInstance();

		$sourceStream = fopen('php://memory', 'wb+');
		$writeChunkSize = 1024;
		$chunk = str_repeat('A', $writeChunkSize);
		$remainingSize = $size;

		while ($remainingSize > 0) {
			$bytesToWrite = min($writeChunkSize, $remainingSize);
			fwrite($sourceStream, ($bytesToWrite === $writeChunkSize) ? $chunk : str_repeat('A', $bytesToWrite));
			$remainingSize -= $bytesToWrite;
		}

		rewind($sourceStream);
		$s3->writeObject('testfilesizes', $sourceStream);

		$this->assertNoUpload('testfilesizes');
		self::assertTrue($s3->objectExists('testfilesizes'), 'Object exists on S3');

		$result = $s3->readObject('testfilesizes');

		// compare first 100 bytes
		self::assertSame(str_repeat('A', 100), fread($result, 100), 'Compare first 100 bytes');

		// compare last 100 bytes
		self::assertSame(0, fseek($result, $size - 100), 'Seek to last 100 bytes succeeds');
		self::assertSame(str_repeat('A', 100), fread($result, 100), 'Compare last 100 bytes');

		// end of file reached
		self::assertSame(0, fseek($result, $size), 'Seek to EOF succeeds');
		self::assertSame($size, ftell($result), 'Pointer is at the end of file');
		self::assertSame('', fread($result, 1), 'Reading at end of file returns no bytes');
		self::assertTrue(feof($result), 'End of file reached after read attempt');

		$this->assertNoUpload('testfilesizes');
	}

	public function testPreSignedMultipartUpload(): void {
		$this->cleanupAfter('presigned');
		$s3 = $this->getInstance();
		if (!$s3->canPreSignUploads()) {
			$this->markTestSkipped('The object store configuration can\'t take presigned uploads');
		}
		$first = str_repeat('A', 5 * 1024 * 1024);
		$last = 'the end';
		$expires = new \DateTimeImmutable('+15 minutes');
		$client = new \GuzzleHttp\Client();
		$put = static function (array $request, string $body) use ($client): int {
			return $client->request($request['method'], $request['url'], [
				'headers' => $request['headers'],
				'body' => $body,
				'http_errors' => false,
			])->getStatusCode();
		};

		$uploadId = $s3->initiatePreSignedMultipartUpload('presigned', 'text/plain');
		$one = $s3->preSignUploadPart('presigned', $uploadId, 1, strlen($first), hash('sha256', $first), $expires);
		$two = $s3->preSignUploadPart('presigned', $uploadId, 2, strlen($last), hash('sha256', $last), $expires);

		$this->assertGreaterThanOrEqual(400, $put($two, 'other bytes'), 'A part that doesn\'t match its checksum is refused');
		$this->assertSame(200, $put($one, $first));
		$this->assertSame(200, $put($two, $last));
		$this->assertSame([
			['number' => 1, 'size' => strlen($first), 'sha256' => hash('sha256', $first)],
			['number' => 2, 'size' => strlen($last), 'sha256' => hash('sha256', $last)],
		], $s3->getPreSignedUploadParts('presigned', $uploadId));

		$size = $s3->completeMultipartUpload('presigned', $uploadId, $s3->getMultipartUploads('presigned', $uploadId));
		$this->assertSame(strlen($first) + strlen($last), $size);
		$this->assertSame($first . $last, stream_get_contents($s3->readObject('presigned')));
		$this->assertNoUpload('presigned');
	}
}
