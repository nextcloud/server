<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Collaboration\Reference;

use OC\Security\RateLimiting\Limiter;
use OC\SystemConfig;
use OCP\Collaboration\Reference\LinkReferenceProvider;
use OCP\Files\AppData\IAppDataFactory;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
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
	private const IMAGE_URL = 'https://website.test/image.png';
	private const GET_OPTIONS = ['timeout' => 3, 'nextcloud' => ['max_body_size' => LinkReferenceProvider::MAX_CONTENT_LENGTH]];

	private IClient&MockObject $client;
	private ISimpleFolder&MockObject $folder;
	private LinkReferenceProvider $provider;
	/** Options of the GET requests */
	private array $getOptions = [];

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
		$this->folder = $this->createMock(ISimpleFolder::class);
		$appData = $this->createMock(IAppData::class);
		$appData->method('getFolder')
			->with('opengraph')
			->willReturn($this->folder);
		$appDataFactory = $this->createMock(IAppDataFactory::class);
		$appDataFactory->method('get')
			->with('core')
			->willReturn($appData);

		$this->provider = new LinkReferenceProvider(
			$clientService,
			$this->createMock(LoggerInterface::class),
			$systemConfig,
			$appDataFactory,
			$this->createMock(IURLGenerator::class),
			$this->createMock(Limiter::class),
			$this->createMock(IUserSession::class),
			$this->createMock(IRequest::class),
		);
	}

	/**
	 * Checks the GET requests after each test, as the provider catches failed assertions during a request
	 */
	#[\Override]
	protected function assertPostConditions(): void {
		parent::assertPostConditions();

		$this->assertNotEmpty($this->getOptions);
		foreach ($this->getOptions as $options) {
			$this->assertSame(self::GET_OPTIONS, $options);
		}
	}

	/**
	 * @param array<string, IResponse|\Exception> $responses response or error by URL
	 */
	private function respond(array $responses): void {
		$this->client->method('get')
			->willReturnCallback(function (string $url, array $options) use ($responses): IResponse {
				$this->getOptions[] = $options;
				if ($responses[$url] instanceof \Exception) {
					throw $responses[$url];
				}
				return $responses[$url];
			});
	}

	private function response(string $contentType, string $body): IResponse {
		return $this->createConfiguredStub(IResponse::class, ['getHeader' => $contentType, 'getBody' => $body]);
	}

	private function page(string $head): IResponse {
		return $this->response('text/html', '<!DOCTYPE html><html><head>' . $head . '</head><body></body></html>');
	}

	public function testResolveReferenceReadsThePage(): void {
		$this->respond([self::URL => $this->page('<meta property="og:title" content="Page title" />')]);

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame('Page title', $reference->getTitle());
	}

	public function testResolveReferenceIgnoresPagesThatCannotBeFetched(): void {
		$this->respond([self::URL => new \RuntimeException('Request failed')]);

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame(self::URL, $reference->getTitle());
	}

	public function testResolveReferenceStoresTheImage(): void {
		$this->respond([
			self::URL => $this->page('<meta property="og:image" content="' . self::IMAGE_URL . '" />'),
			self::IMAGE_URL => $this->response('image/png', 'png-bytes'),
		]);
		$this->folder->expects($this->once())
			->method('newFile')
			->with(md5(self::URL), 'png-bytes');

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertSame('image/png', $reference->getImageContentType());
	}

	public function testResolveReferenceSkipsImagesThatCannotBeFetched(): void {
		$this->respond([
			self::URL => $this->page('<meta property="og:image" content="' . self::IMAGE_URL . '" />'),
			self::IMAGE_URL => new \RuntimeException('Request failed'),
		]);
		$this->folder->expects($this->never())
			->method('newFile');

		$reference = $this->provider->resolveReference(self::URL);

		$this->assertNull($reference->getImageContentType());
	}
}
