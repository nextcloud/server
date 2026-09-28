<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OC\Http\Client\Client;
use OC\Http\Client\ClientService;
use OC\Http\Client\DnsPinMiddleware;
use OCP\Diagnostics\IEventLogger;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ClientServiceTest
 */
class ClientServiceTest extends \Test\TestCase {
	public function testNewClient(): void {
		/** @var IConfig $config */
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')
			->with('dns_pinning', true)
			->willReturn(true);
		/** @var ICertificateManager $certificateManager */
		$certificateManager = $this->createMock(ICertificateManager::class);
		$dnsPinMiddleware = $this->createMock(DnsPinMiddleware::class);
		$dnsPinMiddleware
			->expects($this->atLeastOnce())
			->method('addDnsPinning')
			->willReturn(function (): void {
			});
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$eventLogger = $this->createMock(IEventLogger::class);
		$logger = $this->createMock(LoggerInterface::class);

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator,
			$eventLogger,
			$logger,
		);

		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		$stack->push($dnsPinMiddleware->addDnsPinning());
		$stack->push(Middleware::tap(function (RequestInterface $request) use ($eventLogger): void {
			$eventLogger->start('http:request', $request->getMethod() . ' request to ' . $request->getRequestTarget());
		}, function () use ($eventLogger): void {
			$eventLogger->end('http:request');
		}), 'event logger');
		$guzzleClient = new GuzzleClient(['handler' => $stack]);

		$this->assertEquals(
			new Client(
				$config,
				$certificateManager,
				$guzzleClient,
				$remoteHostValidator,
				$logger,
			),
			$clientService->newClient()
		);
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
			->willReturn(function (): void {
			});
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$eventLogger = $this->createMock(IEventLogger::class);
		$logger = $this->createMock(LoggerInterface::class);

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator,
			$eventLogger,
			$logger,
		);

		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		$stack->push(Middleware::tap(function (RequestInterface $request) use ($eventLogger): void {
			$eventLogger->start('http:request', $request->getMethod() . ' request to ' . $request->getRequestTarget());
		}, function () use ($eventLogger): void {
			$eventLogger->end('http:request');
		}), 'event logger');
		$guzzleClient = new GuzzleClient(['handler' => $stack]);

		$this->assertEquals(
			new Client(
				$config,
				$certificateManager,
				$guzzleClient,
				$remoteHostValidator,
				$logger,
			),
			$clientService->newClient()
		);
	}
}
