<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\Wrapper;
use OC\Files\ObjectStore\S3;
use OC\Memcache\ArrayCache;
use OCP\Files\ObjectStore\ObjectAlreadyExistsException;
use OCP\ICacheFactory;
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

	private function getConfiguredArguments(): array {
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			$this->markTestSkipped('objectstore not configured for s3');
		}
		// Conditional writes are opt-in (default off); enable them for these tests.
		return ['conditional_writes' => 'auto'] + $config['arguments'];
	}

	public function testConditionalWriteRejectsOverwrite(): void {
		$this->cleanupAfter('conditional-write');
		$s3 = new S3($this->getConfiguredArguments());
		if (!$s3->supportsConditionalWrites()) {
			$this->markTestSkipped('the configured object store does not enforce conditional writes');
		}

		$s3->writeObjectIfNotExists('conditional-write', $this->stringToStream('first'));
		self::assertSame('first', stream_get_contents($s3->readObject('conditional-write')));

		$thrown = false;
		try {
			$s3->writeObjectIfNotExists('conditional-write', $this->stringToStream('second'));
		} catch (ObjectAlreadyExistsException) {
			$thrown = true;
		}

		self::assertTrue($thrown, 'A conditional write to an existing key must be refused');
		// The original data must be preserved.
		self::assertSame('first', stream_get_contents($s3->readObject('conditional-write')));
	}

	public function testConditionalWriteRejectsOverwriteMultipart(): void {
		$this->cleanupAfter('conditional-write-mpu');
		$arguments = $this->getConfiguredArguments();
		// Force the multipart-upload path even for tiny objects.
		$arguments['putSizeLimit'] = 1;
		$arguments['uploadPartSize'] = 5 * 1024 * 1024;
		$s3 = new S3($arguments);
		if (!$s3->supportsConditionalWrites()) {
			$this->markTestSkipped('the configured object store does not enforce conditional writes');
		}

		$s3->writeObjectIfNotExists('conditional-write-mpu', $this->stringToStream('first'));
		self::assertSame('first', stream_get_contents($s3->readObject('conditional-write-mpu')));

		$thrown = false;
		try {
			$s3->writeObjectIfNotExists('conditional-write-mpu', $this->stringToStream('second'));
		} catch (ObjectAlreadyExistsException) {
			$thrown = true;
		}

		self::assertTrue($thrown, 'A conditional multipart write to an existing key must be refused');
		self::assertSame('first', stream_get_contents($s3->readObject('conditional-write-mpu')));
	}

	public function testConditionalWritesDisabledByConfig(): void {
		$arguments = $this->getConfiguredArguments();
		$arguments['conditional_writes'] = false;
		$s3 = new S3($arguments);
		self::assertFalse($s3->supportsConditionalWrites());
	}

	public function testConditionalWriteSupportUsesCachedResult(): void {
		$arguments = $this->getConfiguredArguments();

		// Back the cache with a single shared in-memory instance so a stored probe
		// result is observable. In production the distributed cache (e.g. Redis) is a
		// shared backend; the phpunit bootstrap otherwise hands out a fresh, isolated
		// ArrayCache per createDistributed() call, which cannot be observed across calls.
		$cache = new ArrayCache('');
		$factory = $this->createMock(ICacheFactory::class);
		$factory->method('createDistributed')->willReturn($cache);
		$factory->method('createLocal')->willReturn($cache);
		$this->overwriteService(ICacheFactory::class, $factory);

		// Distinct buckets so the process-level probe memo does not carry the first
		// result over into the second assertion, and a pinned hostname so the cache key
		// matches without depending on the configured arguments carrying one.
		$hostname = 'conditional-writes.test';
		$negative = ['bucket' => $arguments['bucket'] . '-cw-neg', 'hostname' => $hostname] + $arguments;
		$positive = ['bucket' => $arguments['bucket'] . '-cw-pos', 'hostname' => $hostname] + $arguments;

		try {
			// A cached negative result is honoured (in 'auto' mode) without probing the store.
			$cache->set($hostname . '::' . $negative['bucket'], 0);
			self::assertFalse((new S3($negative))->supportsConditionalWrites());

			// A cached positive result is likewise reused.
			$cache->set($hostname . '::' . $positive['bucket'], 1);
			self::assertTrue((new S3($positive))->supportsConditionalWrites());

			// An empty or unrecognized mode falls back to disabled, never to 'auto': it
			// must not pick up that positive result, nor probe the store at all.
			foreach (['', 'off'] as $mode) {
				$invalid = ['conditional_writes' => $mode] + $positive;
				self::assertFalse((new S3($invalid))->supportsConditionalWrites(), 'mode: ' . var_export($mode, true));
			}
		} finally {
			$this->restoreService(ICacheFactory::class);
		}
	}
}
