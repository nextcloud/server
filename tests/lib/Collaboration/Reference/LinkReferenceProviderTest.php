<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Reference;

use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Psr7\Utils;
use OC\Security\RateLimiting\Limiter;
use OC\SystemConfig;
use OCP\Collaboration\Reference\LinkReferenceProvider;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class LinkReferenceProviderTest extends TestCase {
	private const URL = 'https://website.test/page';

	private IClient&MockObject $client;
	private LinkReferenceProvider $provider;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock(IClient::class);
		$this->client->method('head')
			->willReturn($this->createConfiguredStub(IResponse::class, ['getHeader' => 'text/html']));
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')
			->willReturn($this->client);
		$systemConfig = $this->createMock(SystemConfig::class);
		$systemConfig->method('getValue')
			->with('reference_opengraph', true)
			->willReturn(true);

		$this->provider = new LinkReferenceProvider(
			$clientService,
			$this->createMock(LoggerInterface::class),
			$systemConfig,
			$this->createMock(IAppDataFactory::class),
			$this->createMock(IURLGenerator::class),
			$this->createMock(Limiter::class),
			$this->createMock(IUserSession::class),
			$this->createMock(IRequest::class),
		);
	}

	/**
	 * Answers the GET request with a stream resource like the body of a streamed HTTP response,
	 * where a single fread() returns at most one chunk
	 */
	/**
	 * @return resource the body of the response
	 */
	private function respondWithStream(string $html) {
		$body = StreamWrapper::getResource(Utils::streamFor($html));
		$this->client->method('get')
			->with(self::URL, ['timeout' => 3, 'stream' => true])
			->willReturn($this->createConfiguredStub(IResponse::class, ['getBody' => $body]));
		return $body;
	}

	public function testResolveReferenceReadsTheWholeStreamedPage(): void {
		$body = $this->respondWithStream('<!DOCTYPE html><html><head><meta property="og:title" content="Streamed page" /></head><body>' . str_repeat('a', 20000) . '</body></html>');

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame('Streamed page', $reference->getTitle());
		$this->assertFalse(is_resource($body));
	}

	public function testResolveReferenceSkipsPagesLargerThanTheLimit(): void {
		$this->respondWithStream('<!DOCTYPE html><html><head><meta property="og:title" content="Large page" /></head><body>' . str_repeat('a', LinkReferenceProvider::MAX_CONTENT_LENGTH) . '</body></html>');

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame(self::URL, $reference->getTitle());
	}

	public function testResolveReferenceIgnoresErrorsWhileReadingThePage(): void {
		$this->client->method('get')
			->willReturn($this->createConfiguredStub(IResponse::class, [
				'getBody' => StreamWrapper::getResource(FnStream::decorate(Utils::streamFor('<html>'), [
					'read' => static fn (): string => throw new \RuntimeException('Connection reset'),
				])),
			]));

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame(self::URL, $reference->getTitle());
	}
}
