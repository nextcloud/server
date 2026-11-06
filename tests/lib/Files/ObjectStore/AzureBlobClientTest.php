<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use OC\Files\ObjectStore\AzureBlobClient;
use OC\Files\ObjectStore\AzureException;
use OC\Files\ObjectStore\AzureSharedKeySigner;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AzureBlobClientTest extends TestCase {
	private IClient&MockObject $httpClient;
	private ITimeFactory&MockObject $timeFactory;
	private AzureBlobClient $client;

	protected function setUp(): void {
		parent::setUp();
		$this->httpClient = $this->createMock(IClient::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->timeFactory->method('getTime')->willReturn(1793966400);
		$this->timeFactory->method('now')->willReturn(new \DateTimeImmutable('@1793966400'));
		$this->client = new AzureBlobClient(
			$this->httpClient,
			new AzureSharedKeySigner('devstoreaccount1', 'bmV4dGNsb3Vk'),
			$this->timeFactory,
			'http://localhost:10000/devstoreaccount1/',
			'2025-01-05',
			['timeout' => 10],
			2,
			0,
		);
	}

	private function response(int $status, array $headers = [], $body = ''): IResponse&MockObject {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getHeader')->willReturnCallback(fn (string $name): string => $headers[$name] ?? '');
		$response->method('getHeaders')->willReturn(array_map(fn (string $value): array => [$value], $headers));
		$response->method('getBody')->willReturn($body);
		return $response;
	}

	public function testPutBlob(): void {
		$body = Utils::streamFor('foobar');
		$this->httpClient->expects($this->once())
			->method('request')
			->willReturnCallback(function (string $method, string $url, array $options) use ($body) {
				$this->assertSame('PUT', $method);
				$this->assertSame('http://localhost:10000/devstoreaccount1/test/urn%3Aoid%3A42', $url);
				$this->assertSame($body, $options['body']);
				$this->assertFalse($options['http_errors']);
				$this->assertTrue($options['nextcloud']['allow_local_address']);
				$this->assertSame(10, $options['timeout']);
				$headers = $options['headers'];
				$this->assertSame('6', $headers['Content-Length']);
				$this->assertSame('BlockBlob', $headers['x-ms-blob-type']);
				$this->assertSame('text/plain', $headers['x-ms-blob-content-type']);
				$this->assertSame('2025-01-05', $headers['x-ms-version']);
				$this->assertSame('Fri, 06 Nov 2026 12:00:00 GMT', $headers['x-ms-date']);
				$this->assertStringStartsWith('SharedKey devstoreaccount1:', $headers['Authorization']);
				return $this->response(201);
			});

		$this->client->putBlob('test', 'urn:oid:42', $body, 6, ['x-ms-blob-content-type' => 'text/plain']);
	}

	public function testPutBlockAndBlockList(): void {
		$blockId = AzureBlobClient::blockId('abc', 1);
		$this->assertSame(base64_encode('abc000001'), $blockId);

		$requests = [];
		$this->httpClient->expects($this->exactly(2))
			->method('request')
			->willReturnCallback(function (string $method, string $url, array $options) use (&$requests) {
				$requests[] = [$url, $options];
				return $this->response(201);
			});

		$this->client->putBlock('test', 'a b/c', $blockId, Utils::streamFor('x'), 1);
		$this->client->putBlockList('test', 'a b/c', [$blockId], ['x-ms-blob-content-type' => 'text/plain']);

		$this->assertSame('http://localhost:10000/devstoreaccount1/test/a%20b/c?comp=block&blockid=' . rawurlencode($blockId), $requests[0][0]);
		$this->assertSame('http://localhost:10000/devstoreaccount1/test/a%20b/c?comp=blocklist', $requests[1][0]);
		$this->assertSame('<?xml version="1.0" encoding="utf-8"?><BlockList><Latest>' . $blockId . '</Latest></BlockList>', $requests[1][1]['body']);
		$this->assertSame('text/plain', $requests[1][1]['headers']['x-ms-blob-content-type']);
		$this->assertSame('application/xml', $requests[1][1]['headers']['Content-Type']);
	}

	public function testGetBlobStreams(): void {
		$response = $this->response(206, ['Content-Range' => 'bytes 0-5/6']);
		$this->httpClient->expects($this->once())
			->method('request')
			->willReturnCallback(function (string $method, string $url, array $options) use ($response) {
				$this->assertSame('GET', $method);
				$this->assertSame('bytes=0-', $options['headers']['Range']);
				$this->assertTrue($options['stream']);
				$this->assertSame('1.1', $options['version']);
				return $response;
			});

		$this->assertSame($response, $this->client->getBlob('test', 'blob', 'bytes=0-'));
	}

	public function testRetriesServerErrorsAndRewindsBody(): void {
		$body = Utils::streamFor('foobar');
		$responses = [$this->response(503), $this->response(500), $this->response(201)];
		$this->httpClient->expects($this->exactly(3))
			->method('request')
			->willReturnCallback(function (string $method, string $url, array $options) use (&$responses) {
				$this->assertSame('foobar', $options['body']->getContents());
				return array_shift($responses);
			});

		$this->client->putBlob('test', 'blob', $body, 6);
	}

	public function testRetriesConnectionErrors(): void {
		$attempts = 0;
		$this->httpClient->expects($this->exactly(2))
			->method('request')
			->willReturnCallback(function () use (&$attempts) {
				if ($attempts++ === 0) {
					throw new ConnectException('Connection refused', new Request('GET', 'http://localhost'));
				}
				return $this->response(202);
			});

		$this->client->deleteBlob('test', 'blob');
	}

	public function testGivesUpAfterRetries(): void {
		$this->httpClient->expects($this->exactly(3))
			->method('request')
			->willReturnCallback(fn () => $this->response(503, ['x-ms-error-code' => 'ServerBusy']));

		try {
			$this->client->deleteBlob('test', 'blob');
			$this->fail('Expected an exception');
		} catch (AzureException $e) {
			$this->assertSame(503, $e->getStatusCode());
			$this->assertSame('ServerBusy', $e->getErrorCode());
		}
	}

	public function testErrorResponse(): void {
		$body = fopen('php://memory', 'w+');
		fwrite($body, "\xEF\xBB\xBF<?xml version=\"1.0\" encoding=\"utf-8\"?><Error><Code>BlobNotFound</Code><Message>The specified blob does not exist.\nRequestId:1</Message></Error>");
		rewind($body);
		$this->httpClient->expects($this->once())
			->method('request')
			->willReturn($this->response(404, [], $body));

		try {
			$this->client->getBlob('test', 'blob');
			$this->fail('Expected an exception');
		} catch (AzureException $e) {
			$this->assertSame(404, $e->getStatusCode());
			$this->assertSame('BlobNotFound', $e->getErrorCode());
			$this->assertSame('GET /devstoreaccount1/test/blob failed with 404 BlobNotFound: The specified blob does not exist.', $e->getMessage());
		}
		$this->assertSame([], libxml_get_errors());
	}

	public function testCreateContainer(): void {
		$responses = [$this->response(201), $this->response(409, ['x-ms-error-code' => 'ContainerAlreadyExists'])];
		$this->httpClient->expects($this->exactly(2))
			->method('request')
			->with('PUT', 'http://localhost:10000/devstoreaccount1/test?restype=container')
			->willReturnCallback(function () use (&$responses) {
				return array_shift($responses);
			});

		$this->assertTrue($this->client->createContainer('test'));
		$this->assertFalse($this->client->createContainer('test'));
	}

	public function testGetBlobProperties(): void {
		$this->httpClient->expects($this->once())
			->method('request')
			->with('HEAD', 'http://localhost:10000/devstoreaccount1/test/blob')
			->willReturn($this->response(200, ['Content-Length' => '6', 'ETag' => '"0x1"']));

		$this->assertSame(['content-length' => '6', 'etag' => '"0x1"'], $this->client->getBlobProperties('test', 'blob'));
	}

	public function testCopyWaitsForPendingCopy(): void {
		$responses = [
			$this->response(202, ['x-ms-copy-status' => 'pending', 'x-ms-copy-id' => 'copy']),
			$this->response(200, ['x-ms-copy-status' => 'pending', 'x-ms-copy-id' => 'copy']),
			$this->response(200, ['x-ms-copy-status' => 'success', 'x-ms-copy-id' => 'copy']),
		];
		$requests = [];
		$this->httpClient->expects($this->exactly(3))
			->method('request')
			->willReturnCallback(function (string $method, string $url, array $options) use (&$responses, &$requests) {
				$requests[] = [$method, $url, $options['headers']['x-ms-copy-source'] ?? null];
				return array_shift($responses);
			});

		$this->client->copyBlob('test', 'source', 'target');

		$this->assertSame([
			['PUT', 'http://localhost:10000/devstoreaccount1/test/target', 'http://localhost:10000/devstoreaccount1/test/source'],
			['HEAD', 'http://localhost:10000/devstoreaccount1/test/target', null],
			['HEAD', 'http://localhost:10000/devstoreaccount1/test/target', null],
		], $requests);
	}

	public function testReadSasUrl(): void {
		$url = $this->client->getReadSasUrl('test', 'urn:oid:42', new \DateTimeImmutable('2026-11-06T14:00:00+01:00'), 'attachment; filename="a.txt"');

		$stringToSign = implode("\n", [
			'r',
			'2026-11-06T11:55:00Z',
			'2026-11-06T13:00:00Z',
			'/blob/devstoreaccount1/test/urn:oid:42',
			'',
			'',
			'https,http',
			'2020-12-06',
			'b',
			'',
			'',
			'',
			'attachment; filename="a.txt"',
			'',
			'',
			'',
		]);
		$signature = base64_encode(hash_hmac('sha256', $stringToSign, base64_decode('bmV4dGNsb3Vk'), true));

		$this->assertSame(
			'http://localhost:10000/devstoreaccount1/test/urn%3Aoid%3A42'
			. '?sp=r&st=2026-11-06T11%3A55%3A00Z&se=2026-11-06T13%3A00%3A00Z&spr=https%2Chttp&sv=2020-12-06&sr=b'
			. '&rscd=attachment%3B%20filename%3D%22a.txt%22&sig=' . rawurlencode($signature),
			$url,
		);
	}

	public function testReadSasUrlHttpsOnly(): void {
		$client = new AzureBlobClient($this->httpClient, new AzureSharedKeySigner('nextcloud', 'bmV4dGNsb3Vk'), $this->timeFactory, 'https://nextcloud.blob.core.windows.net');
		$url = $client->getReadSasUrl('test', 'blob', new \DateTimeImmutable('@1793970000'), 'attachment');
		$this->assertStringStartsWith('https://nextcloud.blob.core.windows.net/test/blob?', $url);
		$this->assertStringContainsString('&spr=https&', $url);
	}

	public function testSupportsSas(): void {
		$this->assertTrue($this->client->supportsSas());
		$client = new AzureBlobClient($this->httpClient, new AzureSharedKeySigner('nextcloud', 'bmV4dGNsb3Vk'), $this->timeFactory, 'https://example.com', '2019-07-07');
		$this->assertFalse($client->supportsSas());
	}

	public function testListBlobs(): void {
		$pages = [
			"\xEF\xBB\xBF<?xml version=\"1.0\" encoding=\"utf-8\"?><EnumerationResults><Blobs>"
				. '<Blob><Name>urn:oid:1</Name><Properties><Last-Modified>Fri, 06 Nov 2026 12:00:00 GMT</Last-Modified><Content-Length>6</Content-Length></Properties>'
				. '<Metadata><original_path>files/a.txt</original_path></Metadata></Blob>'
				. '</Blobs><NextMarker>page2</NextMarker></EnumerationResults>',
			'<?xml version="1.0" encoding="utf-8"?><EnumerationResults><Blobs><Blob><Name>urn:oid:2</Name></Blob></Blobs><NextMarker/></EnumerationResults>',
		];
		$urls = [];
		$this->httpClient->expects($this->exactly(2))
			->method('request')
			->willReturnCallback(function (string $method, string $url) use (&$pages, &$urls) {
				$urls[] = $url;
				return $this->response(200, [], array_shift($pages));
			});

		$blobs = iterator_to_array($this->client->listBlobs('test', 'urn:oid:'), false);

		$this->assertSame([
			'http://localhost:10000/devstoreaccount1/test?restype=container&comp=list&include=metadata&prefix=urn%3Aoid%3A',
			'http://localhost:10000/devstoreaccount1/test?restype=container&comp=list&include=metadata&prefix=urn%3Aoid%3A&marker=page2',
		], $urls);
		$this->assertSame([
			[
				'name' => 'urn:oid:1',
				'properties' => ['last-modified' => 'Fri, 06 Nov 2026 12:00:00 GMT', 'content-length' => '6'],
				'metadata' => ['original_path' => 'files/a.txt'],
			],
			['name' => 'urn:oid:2', 'properties' => [], 'metadata' => []],
		], $blobs);
	}

	public function testCopyFailure(): void {
		$responses = [
			$this->response(202, ['x-ms-copy-status' => 'pending', 'x-ms-copy-id' => 'copy']),
			$this->response(200, ['x-ms-copy-status' => 'failed', 'x-ms-copy-id' => 'copy', 'x-ms-copy-status-description' => 'broken']),
		];
		$this->httpClient->method('request')
			->willReturnCallback(function () use (&$responses) {
				return array_shift($responses);
			});

		$this->expectException(AzureException::class);
		$this->expectExceptionMessage('Copy of source to target failed with status failed: broken');
		$this->client->copyBlob('test', 'source', 'target');
	}
}
