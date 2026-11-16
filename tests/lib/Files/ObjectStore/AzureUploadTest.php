<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\CountWrapper;
use OC\Files\ObjectStore\Azure;
use OC\Files\ObjectStore\AzureBlobClient;
use OC\Files\ObjectStore\AzureException;
use OC\Files\ObjectStore\ContentDisposition;
use OCP\Files\NotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\StreamInterface;
use Test\TestCase;

/**
 * Azure object store against a mocked blob client
 */
class AzureUploadTest extends TestCase {
	private AzureBlobClient&MockObject $blobClient;

	protected function setUp(): void {
		parent::setUp();
		$this->blobClient = $this->createMock(AzureBlobClient::class);
	}

	private function getInstance(int $blockSize = 4, array $arguments = []): Azure {
		return new Azure($arguments + [
			'container' => 'test',
			'account_name' => 'nextcloud',
			'account_key' => 'bmV4dGNsb3Vk',
			'upload_block_size' => $blockSize,
		], $this->blobClient);
	}

	private function unknownSizeStream(string $data) {
		$stream = fopen('php://memory', 'w+');
		fwrite($stream, $data);
		rewind($stream);
		return CountWrapper::wrap(NonSeekableStream::wrap($stream), function (): void {
		});
	}

	public static function dataSingleRequest(): array {
		return [
			'empty' => [''],
			'smaller than a block' => ['abc'],
		];
	}

	#[DataProvider('dataSingleRequest')]
	public function testSingleRequestUpload(string $data): void {
		$this->blobClient->expects($this->once())
			->method('putBlob')
			->willReturnCallback(function (string $container, string $blob, StreamInterface $body, int $size, array $headers) use ($data): void {
				$this->assertSame('test', $container);
				$this->assertSame('urn:oid:1', $blob);
				$this->assertSame($data, (string)$body);
				$this->assertSame(strlen($data), $size);
				$this->assertSame(['x-ms-blob-content-type' => 'text/plain'], $headers);
			});
		$this->blobClient->expects($this->never())->method('putBlock');
		$this->blobClient->expects($this->never())->method('putBlockList');

		$this->getInstance()->writeObject('urn:oid:1', $this->unknownSizeStream($data), 'text/plain');
	}

	public static function dataBlockUpload(): array {
		return [
			'exactly one block' => ['abcd', ['abcd']],
			'partial last block' => ['abcdefghij', ['abcd', 'efgh', 'ij']],
			'full last block' => ['abcdefgh', ['abcd', 'efgh']],
		];
	}

	#[DataProvider('dataBlockUpload')]
	public function testBlockUpload(string $data, array $expectedBlocks): void {
		$blocks = [];
		$this->blobClient->expects($this->never())->method('putBlob');
		$this->blobClient->expects($this->exactly(count($expectedBlocks)))
			->method('putBlock')
			->willReturnCallback(function (string $container, string $blob, string $blockId, StreamInterface $body, int $size) use (&$blocks): void {
				$this->assertSame(strlen((string)$body), $size);
				$blocks[$blockId] = (string)$body;
			});
		$this->blobClient->expects($this->once())
			->method('putBlockList')
			->willReturnCallback(function (string $container, string $blob, array $blockIds, array $headers) use (&$blocks, $expectedBlocks): void {
				$this->assertSame(array_keys($blocks), $blockIds);
				$this->assertSame($expectedBlocks, array_values($blocks));
				$this->assertCount(1, array_unique(array_map('strlen', $blockIds)), 'All block ids of a blob have the same length');
				$this->assertSame([], $headers);
			});

		$this->getInstance()->writeObject('urn:oid:1', $this->unknownSizeStream($data));
	}

	public function testBlockIdsDifferBetweenUploads(): void {
		$blockIds = [];
		$this->blobClient->method('putBlock')
			->willReturnCallback(function (string $container, string $blob, string $blockId) use (&$blockIds): void {
				$blockIds[] = $blockId;
			});

		$instance = $this->getInstance();
		$instance->writeObject('urn:oid:1', $this->unknownSizeStream('abcd'));
		$instance->writeObject('urn:oid:1', $this->unknownSizeStream('abcd'));

		$this->assertCount(2, array_unique($blockIds));
	}

	public function testInvalidBlockSize(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->getInstance(0);
	}

	public function testReadNotFound(): void {
		$this->blobClient->method('getBlob')
			->willThrowException(new AzureException('not found', 404, 'BlobNotFound'));

		$this->expectException(NotFoundException::class);
		@$this->getInstance()->readObject('urn:oid:1');
	}

	public function testReadFailure(): void {
		$this->blobClient->method('getBlob')
			->willThrowException(new AzureException('forbidden', 403, 'AuthenticationFailed'));

		try {
			@$this->getInstance()->readObject('urn:oid:1');
			$this->fail('Expected an exception');
		} catch (\Exception $e) {
			$this->assertNotInstanceOf(NotFoundException::class, $e);
			$this->assertInstanceOf(AzureException::class, $e->getPrevious());
		}
	}

	public function testObjectExists(): void {
		$this->blobClient->method('getBlobProperties')
			->willReturnCallback(function (string $container, string $blob): array {
				if ($blob === 'missing') {
					throw new AzureException('not found', 404, 'BlobNotFound');
				}
				if ($blob === 'forbidden') {
					throw new AzureException('forbidden', 403, 'AuthenticationFailed');
				}
				return [];
			});

		$instance = $this->getInstance();
		$this->assertTrue($instance->objectExists('existing'));
		$this->assertFalse($instance->objectExists('missing'));
		$this->expectException(AzureException::class);
		$instance->objectExists('forbidden');
	}

	public function testPreSignedUrlDisabledByDefault(): void {
		$this->blobClient->expects($this->never())->method('getReadSasUrl');
		$this->assertNull($this->getInstance()->preSignedUrl('urn:oid:1', new \DateTimeImmutable('+1 hour'), 'a.txt'));
	}

	public function testPreSignedUrl(): void {
		$expiration = new \DateTimeImmutable('+1 hour');
		$this->blobClient->method('supportsSas')->willReturn(true);
		$this->blobClient->expects($this->once())
			->method('getReadSasUrl')
			->with('test', 'urn:oid:1', $expiration, ContentDisposition::attachment('a b.txt'))
			->willReturn('https://example.com/signed');

		$instance = $this->getInstance(arguments: ['use_presigned_url' => true]);
		$this->assertSame('https://example.com/signed', $instance->preSignedUrl('urn:oid:1', $expiration, 'a b.txt'));
	}

	public function testPreSignedUrlUnsupportedVersion(): void {
		$this->blobClient->method('supportsSas')->willReturn(false);
		$this->blobClient->expects($this->never())->method('getReadSasUrl');

		$instance = $this->getInstance(arguments: ['use_presigned_url' => true]);
		$this->assertNull($instance->preSignedUrl('urn:oid:1', new \DateTimeImmutable('+1 hour')));
	}

	public function testWriteObjectWithMetaData(): void {
		$this->blobClient->expects($this->once())
			->method('putBlob')
			->with('test', 'urn:oid:1', $this->anything(), 3, [
				'x-ms-blob-content-type' => 'text/plain',
				'x-ms-meta-original_storage' => 'home::alice',
				'x-ms-meta-original_path' => 'base64:' . base64_encode('files/Grüße.txt'),
				'x-ms-meta-filename' => 'base64:' . base64_encode(' padded '),
			]);

		$this->getInstance()->writeObjectWithMetaData('urn:oid:1', $this->unknownSizeStream('abc'), [
			'mimetype' => 'text/plain',
			'size' => 3,
			'original-storage' => 'home::alice',
			'original-path' => 'files/Grüße.txt',
			'filename' => ' padded ',
		]);
	}

	public function testGetObjectMetaData(): void {
		$this->blobClient->method('getBlobProperties')
			->with('test', 'urn:oid:1')
			->willReturn([
				'last-modified' => 'Fri, 06 Nov 2026 12:00:00 GMT',
				'etag' => '"0x8DDC"',
				'content-length' => '3',
				'content-type' => 'text/plain',
				'x-ms-meta-original_path' => 'base64:' . base64_encode('files/Grüße.txt'),
				'x-ms-meta-original_storage' => 'home::alice',
				'x-ms-version' => '2025-01-05',
			]);

		$this->assertEquals([
			'mtime' => new \DateTime('2026-11-06T12:00:00Z'),
			'etag' => '0x8DDC',
			'size' => 3,
			'mimetype' => 'text/plain',
			'original-path' => 'files/Grüße.txt',
			'original-storage' => 'home::alice',
		], $this->getInstance()->getObjectMetaData('urn:oid:1'));
	}

	public function testListObjects(): void {
		$this->blobClient->method('listBlobs')
			->with('test', 'urn:oid:')
			->willReturnCallback(function () {
				yield ['name' => 'urn:oid:1', 'properties' => ['content-length' => '3'], 'metadata' => ['original_path' => 'files/a.txt']];
				yield ['name' => 'urn:oid:2', 'properties' => [], 'metadata' => []];
			});

		$this->assertSame([
			['urn' => 'urn:oid:1', 'metadata' => ['size' => 3, 'original-path' => 'files/a.txt']],
			['urn' => 'urn:oid:2', 'metadata' => []],
		], iterator_to_array($this->getInstance()->listObjects('urn:oid:'), false));
	}

	public function testMultipartUpload(): void {
		$instance = $this->getInstance();
		$uploadId = $instance->initiateMultipartUpload('urn:oid:1');
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uploadId);
		$this->assertNotSame($uploadId, $instance->initiateMultipartUpload('urn:oid:1'));

		$this->blobClient->expects($this->once())
			->method('putBlock')
			->willReturnCallback(function (string $container, string $blob, string $blockId, StreamInterface $body, int $size) use ($uploadId): void {
				$this->assertSame('urn:oid:1', $blob);
				$this->assertSame(AzureBlobClient::blockId($uploadId, 2), $blockId);
				$this->assertSame('chunk', (string)$body);
				$this->assertSame(5, $size);
			});
		$this->assertSame(
			['ETag' => AzureBlobClient::blockId($uploadId, 2)],
			$instance->uploadMultipartPart('urn:oid:1', $uploadId, 2, $this->unknownSizeStream('chunk'), 5),
		);
	}

	public function testGetMultipartUploads(): void {
		$uploadId = str_repeat('a', 32);
		$this->blobClient->method('getUncommittedBlocks')
			->with('test', 'urn:oid:1')
			->willReturn([
				['name' => AzureBlobClient::blockId($uploadId, 2), 'size' => 3],
				['name' => AzureBlobClient::blockId(str_repeat('b', 32), 1), 'size' => 7],
				['name' => AzureBlobClient::blockId($uploadId, 1), 'size' => 5],
			]);

		$this->assertSame([
			['PartNumber' => 1, 'ETag' => AzureBlobClient::blockId($uploadId, 1), 'Size' => 5, 'LastModified' => null],
			['PartNumber' => 2, 'ETag' => AzureBlobClient::blockId($uploadId, 2), 'Size' => 3, 'LastModified' => null],
		], $this->getInstance()->getMultipartUploads('urn:oid:1', $uploadId));
	}

	public function testCompleteMultipartUpload(): void {
		$this->blobClient->expects($this->once())
			->method('putBlockList')
			->with('test', 'urn:oid:1', ['block1', 'block2']);
		$this->blobClient->method('getBlobProperties')->willReturn(['content-length' => '8']);

		$this->assertSame(8, $this->getInstance()->completeMultipartUpload('urn:oid:1', 'upload', [
			['PartNumber' => 1, 'ETag' => 'block1', 'Size' => 5],
			['PartNumber' => 2, 'ETag' => 'block2', 'Size' => 3],
		]));
	}

	public function testStorageId(): void {
		$this->assertSame('azure::blob::test', $this->getInstance()->getStorageId());
	}
}
