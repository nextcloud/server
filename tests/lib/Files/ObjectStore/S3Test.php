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

	public function dir_opendir($path, $options) {
		return false;
	}

	public function stream_open($path, $mode, $options, &$opened_path) {
		$this->loadContext('nonseek');
		return true;
	}

	public function stream_seek($offset, $whence = SEEK_SET) {
		return false;
	}
}

/**
 * @group PRIMARY-s3
 */
class S3Test extends ObjectStoreTestCase {
	public function setUp(): void {
		parent::setUp();
		$s3 = $this->getInstance();
		$s3->deleteObject('multiparttest');
	}

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
		/** @var \OC\Files\ObjectStore\S3 */
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
	}

	/** File size to upload in bytes */
	public static function dataFileSizes(): array {
		return [
			[1000000], [2000000], [5242879], [5242880], [5242881], [10000000]
		];
	}

	/** @dataProvider dataFileSizes */
	public function testFileSizes($size): void {
		if (str_starts_with(PHP_VERSION, '8.3') && getenv('CI')) {
			$this->markTestSkipped('Test is unreliable and skipped on 8.3');
		}

		$this->cleanupAfter('testfilesizes');
		$s3 = $this->getInstance();

		$sourceStream = fopen('php://memory', 'wb+');
		$writeChunkSize = 1024;
		$chunkCount = $size / $writeChunkSize;
		for ($i = 0; $i < $chunkCount; $i++) {
			fwrite($sourceStream, str_repeat('A',
				($i < $chunkCount - 1) ? $writeChunkSize : $size - ($i * $writeChunkSize)
			));
		}
		rewind($sourceStream);
		$s3->writeObject('testfilesizes', $sourceStream);

		$this->assertNoUpload('testfilesizes');
		self::assertTrue($s3->objectExists('testfilesizes'), 'Object exists on S3');

		$result = $s3->readObject('testfilesizes');

		// compare first 100 bytes
		self::assertEquals(str_repeat('A', 100), fread($result, 100), 'Compare first 100 bytes');

		// compare last 100 bytes
		fseek($result, $size - 100);
		self::assertEquals(str_repeat('A', 100), fread($result, 100), 'Compare last 100 bytes');

		// end of file reached
		fseek($result, $size);
		self::assertTrue(feof($result), 'End of file reached');

		$this->assertNoUpload('testfilesizes');
	}
}
