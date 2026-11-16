<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\AzureSharedKeySigner;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class AzureSharedKeySignerTest extends TestCase {
	private const KEY = 'bmV4dGNsb3Vk';
	private const DATE = 'Fri, 06 Nov 2026 12:00:00 GMT';

	/**
	 * Signatures computed by the SharedKeyAuthScheme of microsoft/azure-storage-common 1.5.2
	 */
	public static function dataAuthorizationHeader(): array {
		return [
			'put blob' => [
				'nextcloud', 'PUT', '/container/urn%3Aoid%3A42', [],
				['Content-Length' => '6', 'x-ms-blob-type' => 'BlockBlob', 'x-ms-blob-content-type' => 'text/plain', 'x-ms-date' => self::DATE, 'x-ms-version' => '2025-01-05'],
				'SharedKey nextcloud:I9mdOZGdH3h9VDcC6xXYZQ/EcPQ38h33CWZau5XRGCk=',
			],
			'put block, path-style endpoint' => [
				'devstoreaccount1', 'PUT', '/devstoreaccount1/test/urn%3Aoid%3A42', ['comp' => 'block', 'blockid' => 'YWJjMDAwMDAx'],
				['Content-Length' => '1024', 'x-ms-date' => self::DATE, 'x-ms-version' => '2025-01-05'],
				'SharedKey devstoreaccount1:L5xJrsHGzXNDFeAOk8WjpynIf9rQRzxA18pA0g6w738=',
			],
			'ranged get' => [
				'nextcloud', 'GET', '/container/urn%3Aoid%3A42', [],
				['Range' => 'bytes=100-', 'x-ms-date' => self::DATE, 'x-ms-version' => '2025-01-05'],
				'SharedKey nextcloud:x50B2feINs6CKMndPFXa9oZzvUP5gAHflcv42idJU3Q=',
			],
			'copy with mixed case header' => [
				'nextcloud', 'PUT', '/container/target', [],
				['Content-Length' => '1', 'x-ms-copy-source' => 'https://nextcloud.blob.core.windows.net/container/source', 'X-MS-Date' => self::DATE, 'x-ms-version' => '2025-01-05'],
				'SharedKey nextcloud:QOk8TBLyHUzUM/BHgsscagJHk6MmINg4aKat09zg6WY=',
			],
		];
	}

	#[DataProvider('dataAuthorizationHeader')]
	public function testAuthorizationHeader(string $account, string $method, string $path, array $query, array $headers, string $expected): void {
		$signer = new AzureSharedKeySigner($account, self::KEY);
		$this->assertSame($expected, $signer->getAuthorizationHeader($method, $path, $query, $headers));
	}

	public function testStringToSign(): void {
		$signer = new AzureSharedKeySigner('nextcloud', self::KEY);
		$stringToSign = $signer->getStringToSign('put', '/container/blob', ['Comp' => 'block', 'blockid' => 'a+b/'], [
			'Content-Length' => '0',
			'Content-Type' => 'application/xml',
			'x-ms-version' => '2025-01-05',
			'X-Ms-Date' => self::DATE,
			'x-ms-meta-name' => "  a \t b  ",
			'Authorization' => 'ignored',
		]);

		$this->assertSame(implode("\n", [
			'PUT',
			'',
			'',
			// a zero content length is signed as an empty string
			'',
			'',
			'application/xml',
			'',
			'',
			'',
			'',
			'',
			'',
			'x-ms-date:' . self::DATE,
			'x-ms-meta-name:a b',
			'x-ms-version:2025-01-05',
			'/nextcloud/container/blob',
			'blockid:a+b/',
			'comp:block',
		]), $stringToSign);
	}

	public function testInvalidKey(): void {
		$this->expectException(\InvalidArgumentException::class);
		new AzureSharedKeySigner('nextcloud', 'not base64!');
	}
}
