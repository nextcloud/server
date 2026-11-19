<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\S3;
use OC\Files\ObjectStore\StorageObjectStore;
use OC\Files\Storage\Temporary;
use OCP\Files\ObjectStore\IObjectStoreStorage;
use Test\TestCase;

/**
 * Signing presigned part uploads happens locally, so it needs no S3 server.
 */
class S3PresignedUploadTest extends TestCase {
	private const SHA256 = 'a29968fad2e782aa9f2040a35f05adb97ed8979eb1f572c8c8ea78637e275f3c';

	private function store(array $extra = []): S3 {
		return new S3($extra + [
			'bucket' => 'nextcloud',
			'hostname' => 's3.example.com',
			'port' => 443,
			'region' => 'eu-central-1',
			'key' => 'AKIDEXAMPLE',
			'secret' => 'wJalrXUtnFEMI/K7MDENG+bPxRfiCYEXAMPLEKEY',
			'use_path_style' => true,
			'verify_bucket_exists' => false,
		]);
	}

	public function testPresignsOnePartWithItsChecksum(): void {
		$expires = new \DateTimeImmutable('+15 minutes');
		$request = $this->store()->preSignUploadPart('urn:oid:42', 'upload-1', 3, 5242880, self::SHA256, $expires);

		$this->assertSame('PUT', $request['method']);
		$this->assertSame(['x-amz-checksum-sha256' => base64_encode(hex2bin(self::SHA256))], $request['headers']);
		$url = parse_url($request['url']);
		$this->assertSame('https', $url['scheme']);
		$this->assertSame('s3.example.com', $url['host']);
		$this->assertSame('/nextcloud/urn%3Aoid%3A42', $url['path']);
		parse_str($url['query'], $query);
		$this->assertSame('upload-1', $query['uploadId']);
		$this->assertSame('3', $query['partNumber']);
		$this->assertSame('900', $query['X-Amz-Expires']);
		$this->assertContains('x-amz-checksum-sha256', explode(';', $query['X-Amz-SignedHeaders']));
		$this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $query['X-Amz-Signature']);
		$this->assertStringNotContainsString('wJalrXUtnFEMI', $request['url']);
	}

	public static function invalidParts(): array {
		return [
			'part 0' => [0, 1, self::SHA256],
			'part 10001' => [10001, 1, self::SHA256],
			'negative size' => [1, -1, self::SHA256],
			'larger than 5 GiB' => [1, 5 * 1024 * 1024 * 1024 + 1, self::SHA256],
			'uppercase hash' => [1, 1, strtoupper(self::SHA256)],
			'short hash' => [1, 1, 'abc'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('invalidParts')]
	public function testRefusesPartsOutOfRange(int $number, int $size, string $sha256): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->store()->preSignUploadPart('urn:oid:42', 'upload-1', $number, $size, $sha256, new \DateTimeImmutable('+15 minutes'));
	}

	public function testCanPreSignOnlyWithoutCustomerKeysAndWithV4Signatures(): void {
		$this->assertTrue($this->store()->canPreSignUploads());
		$this->assertTrue($this->store(['sse_kms_enabled' => true])->canPreSignUploads());
		$this->assertFalse($this->store(['sse_c_key' => base64_encode(random_bytes(32))])->canPreSignUploads());
		$this->assertFalse($this->store(['legacy_auth' => true])->canPreSignUploads());
	}

	public function testRefusesToPresignWithACustomerKey(): void {
		$this->expectException(\Exception::class);
		$this->store(['sse_c_key' => base64_encode(random_bytes(32))])
			->preSignUploadPart('urn:oid:42', 'upload-1', 1, 1, self::SHA256, new \DateTimeImmutable('+15 minutes'));
	}

	public function testObjectStoreStorageNamesItsObjects(): void {
		$objectStore = new StorageObjectStore(new Temporary([]));
		$storage = new ObjectStoreStorage(['objectstore' => $objectStore, 'objectPrefix' => 'urn:test:']);

		$this->assertInstanceOf(IObjectStoreStorage::class, $storage);
		$this->assertTrue($storage->instanceOfStorage(IObjectStoreStorage::class));
		$this->assertSame($objectStore, $storage->getObjectStore());
		$this->assertSame('urn:test:7', $storage->getURN(7));
	}
}
