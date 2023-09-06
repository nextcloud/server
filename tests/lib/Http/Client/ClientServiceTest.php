<?php

declare(strict_types=1);

/**
 * Copyright (c) 2015 Lukas Reschke <lukas@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Http\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use OC\Http\Client\Client;
use OC\Http\Client\ClientService;
use OC\Http\Client\DnsPinMiddleware;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;

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
			->willReturn(function () {
			});
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator
		);

		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		$stack->push($dnsPinMiddleware->addDnsPinning());
		$guzzleClient = new GuzzleClient(['handler' => $stack]);

		$this->assertEquals(
			new Client(
				$config,
				$certificateManager,
				$guzzleClient,
				$remoteHostValidator,
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
			->willReturn(function () {
			});
		$remoteHostValidator = $this->createMock(IRemoteHostValidator::class);

		$clientService = new ClientService(
			$config,
			$certificateManager,
			$dnsPinMiddleware,
			$remoteHostValidator,
		);

		$handler = new CurlHandler();
		$stack = HandlerStack::create($handler);
		$guzzleClient = new GuzzleClient(['handler' => $stack]);

		$this->assertEquals(
			new Client(
				$config,
				$certificateManager,
				$guzzleClient,
				$remoteHostValidator
			),
			$clientService->newClient()
		);
	}
}
