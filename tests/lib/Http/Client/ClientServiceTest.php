<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use OC\Http\Client\Client;
use OC\Http\Client\ClientService;
use OC\Http\Client\DnsPinMiddleware;
use OCP\Diagnostics\IEventLogger;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use OCP\ServerVersion;
use Psr\Log\LoggerInterface;

/**
 * Class ClientServiceTest
 */
class ClientServiceTest extends \Test\TestCase {
	public function testNewClient(): void {
		/** @var IConfig $config */
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturnMap([
			['dns_pinning', true, true],
			['installed', false, false],
			['allow_local_remote_servers', false, false],
			['http_client_add_user_agent_url', false, false],
		]);
		/** @var ICertificateManager $certificateManager */
		$certificateManager = $this->createMock(ICertificateManager::class);
		$dnsPinMiddleware = $this->createMock(DnsPinMiddleware::class);
		$dnsMiddleware = static fn (callable $handler): callable => $handler;
		$dnsPinMiddleware
			->expects($this->atLeastOnce())
			->method('addDnsPinning')
			->willReturn($dnsMiddleware);
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$remoteHostValidator->method('isValid')->willReturn(true);
		$eventLogger = $this->createMock(IEventLogger::class);
		$eventLogger->expects($this->once())
			->method('start')
			->with('http:request', 'GET request to /');
		$eventLogger->expects($this->once())
			->method('end')
			->with('http:request');
		$logger = $this->createMock(LoggerInterface::class);
		$serverVersion = $this->createMock(ServerVersion::class);
		$serverVersion->method('getVersionString')->willReturn('1.0.0');
		$config->method('getSystemValueString')->willReturnMap([
			['proxy', '', ''],
			['overwrite.cli.url', '', ''],
		]);
		$config->method('getSystemValue')->with('proxyexclude', [])->willReturn([]);
		$certificateManager->method('getDefaultCertificatesBundlePath')->willReturn('/tmp/certificates.crt');

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator,
			$eventLogger,
			$logger,
			$serverVersion,
		);

		$client = $clientService->newClient();
		$this->assertEquals(new Client(
			$config,
			$certificateManager,
			$this->getGuzzleClient($client),
			$remoteHostValidator,
			$logger,
			$serverVersion,
		), $client);

		$stack = $this->getHandlerStack($client);
		$this->assertStringContainsString("Name: ''", (string)$stack);
		$this->assertStringContainsString("Name: 'event logger'", (string)$stack);

		$stack->setHandler(new MockHandler([new Response(200)]));
		$this->assertSame(200, $client->get('https://example.com')->getStatusCode());
	}

	public function testDisableDnsPinning(): void {
		/** @var IConfig $config */
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('dns_pinning', true)
			->willReturn(false);
		/** @var ICertificateManager $certificateManager */
		$certificateManager = $this->createMock(ICertificateManager::class);
		$dnsPinMiddleware = $this->createMock(DnsPinMiddleware::class);
		$dnsPinMiddleware
			->expects($this->never())
			->method('addDnsPinning')
			->willReturn(static fn (callable $handler): callable => $handler);
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$remoteHostValidator->method('isValid')->willReturn(true);
		$eventLogger = $this->createMock(IEventLogger::class);
		$logger = $this->createMock(LoggerInterface::class);
		$serverVersion = $this->createMock(ServerVersion::class);
		$serverVersion->method('getVersionString')->willReturn('1.0.0');
		$config->method('getSystemValueBool')->willReturnMap([
			['dns_pinning', true, false],
			['installed', false, false],
			['allow_local_remote_servers', false, false],
			['http_client_add_user_agent_url', false, false],
		]);
		$config->method('getSystemValueString')->willReturnMap([
			['proxy', '', ''],
			['overwrite.cli.url', '', ''],
		]);
		$config->method('getSystemValue')->with('proxyexclude', [])->willReturn([]);
		$certificateManager->method('getDefaultCertificatesBundlePath')->willReturn('/tmp/certificates.crt');

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator,
			$eventLogger,
			$logger,
			$serverVersion,
		);

		$client = $clientService->newClient();
		$this->assertEquals(new Client(
			$config,
			$certificateManager,
			$this->getGuzzleClient($client),
			$remoteHostValidator,
			$logger,
			$serverVersion,
		), $client);

		$stack = $this->getHandlerStack($client);
		$this->assertStringNotContainsString("Name: ''", (string)$stack);
		$this->assertStringContainsString("Name: 'event logger'", (string)$stack);
	}

	private function getGuzzleClient(Client $client): \GuzzleHttp\Client {
		return self::invokePrivate($client, 'client');
	}

	private function getHandlerStack(Client $client): HandlerStack {
		/** @var HandlerStack $stack */
		$stack = $this->getGuzzleClient($client)->getConfig('handler');
		return $stack;
	}
}
