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

	public function testStorageId(): void {
		$this->assertSame('azure::blob::test', $this->getInstance()->getStorageId());
	}
}
