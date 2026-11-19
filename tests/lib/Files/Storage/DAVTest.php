<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use OC\Files\Storage\DAV;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Test\TestCase;

class DAVTest extends TestCase {
	#[\Override]
	protected function tearDown(): void {
		$this->restoreService(IClientService::class);
		parent::tearDown();
	}

	public function testFopenFillsEachReadOfANetworkStream(): void {
		$source = Utils::streamFor(str_repeat('a', 20000));
		// A response body that returns at most 1000 bytes per read, like a network stream
		$body = StreamWrapper::getResource(FnStream::decorate($source, [
			'read' => static fn (int $length): string => $source->read(min($length, 1000)),
		]));
		$client = $this->createMock(IClient::class);
		$client->method('get')
			->willReturn($this->createConfiguredStub(IResponse::class, ['getStatusCode' => 200, 'getBody' => $body]));
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')
			->willReturn($client);
		$this->overwriteService(IClientService::class, $clientService);
		$storage = new DAV(['host' => 'dav.test', 'user' => 'user', 'password' => 'password', 'root' => '/']);

		$handle = $storage->fopen('file.txt', 'r');

		$this->assertSame(8192, strlen(fread($handle, 8192)));
	}
}
