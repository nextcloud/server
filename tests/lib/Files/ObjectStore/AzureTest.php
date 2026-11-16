<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use Icewind\Streams\CountWrapper;
use OC\Files\ObjectStore\Azure;
use OC\Files\ObjectStore\ContentDisposition;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Server;

#[\PHPUnit\Framework\Attributes\Group('PRIMARY-azure')]
class AzureTest extends ObjectStoreTestCase {
	#[\Override]
	protected function getInstance(array $arguments = []) {
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== 'OC\\Files\\ObjectStore\\Azure') {
			$this->markTestSkipped('objectstore not configured for azure');
		}

		return new Azure($arguments + $config['arguments']);
	}

	public static function dataBlockUpload(): array {
		return [
			'smaller than one block' => [100, 1024],
			'exactly one block' => [1024, 1024],
			'several blocks' => [1024, 3000],
			'several full blocks' => [1024, 3072],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataBlockUpload')]
	public function testBlockUpload(int $blockSize, int $size): void {
		$this->cleanupAfter('block-upload');
		$data = random_bytes($size);

		$instance = $this->getInstance(['upload_block_size' => $blockSize]);
		$stream = CountWrapper::wrap(NonSeekableStream::wrap($this->stringToStream($data)), function (): void {
		});
		$instance->writeObject('block-upload', $stream, 'application/octet-stream');

		$this->assertSame($data, stream_get_contents($instance->readObject('block-upload')));
	}

	public function testPreSignedUrl(): void {
		$this->cleanupAfter('presigned');
		$instance = $this->getInstance(['use_presigned_url' => true]);
		$instance->writeObject('presigned', $this->stringToStream('foobar'), 'text/plain');

		$url = $instance->preSignedUrl('presigned', new \DateTimeImmutable('+5 minutes'), 'Grüße.txt');
		$this->assertNotNull($url);

		$client = Server::get(IClientService::class)->newClient();
		$response = $client->get($url, ['nextcloud' => ['allow_local_address' => true]]);
		$this->assertSame('foobar', $response->getBody());
		$this->assertSame(ContentDisposition::attachment('Grüße.txt'), $response->getHeader('Content-Disposition'));

		$expired = $instance->preSignedUrl('presigned', new \DateTimeImmutable('-1 minute'));
		$response = $client->get($expired, ['nextcloud' => ['allow_local_address' => true], 'http_errors' => false]);
		$this->assertSame(403, $response->getStatusCode());
	}

	public function testMetaData(): void {
		$this->cleanupAfter('urn:oid:metadata');
		$instance = $this->getInstance();
		$instance->writeObjectWithMetaData('urn:oid:metadata', $this->stringToStream('foobar'), [
			'mimetype' => 'text/plain',
			'original-path' => 'files/Grüße.txt',
		]);

		$metaData = $instance->getObjectMetaData('urn:oid:metadata');
		$this->assertSame(6, $metaData['size']);
		$this->assertSame('text/plain', $metaData['mimetype']);
		$this->assertSame('files/Grüße.txt', $metaData['original-path']);
		$this->assertInstanceOf(\DateTime::class, $metaData['mtime']);

		$listed = array_values(array_filter(
			iterator_to_array($instance->listObjects('urn:oid:'), false),
			fn (array $object): bool => $object['urn'] === 'urn:oid:metadata',
		));
		$this->assertCount(1, $listed);
		$this->assertSame(6, $listed[0]['metadata']['size']);
		$this->assertSame('files/Grüße.txt', $listed[0]['metadata']['original-path']);
	}

	public function testReadNonExistingThrowsNotFound(): void {
		$this->expectException(\OCP\Files\NotFoundException::class);
		@$this->getInstance()->readObject('non-existing');
	}
}
