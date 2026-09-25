<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Http\WellKnown;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Http\WellKnown\RequestManager;
use OCP\AppFramework\QueryException;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\Http\WellKnown\JrdResponse;
use OCP\IRequest;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;
use function get_class;

class RequestManagerTest extends TestCase {
	private RequestManager $manager;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->manager = $this->createInstanceWithMocks(RequestManager::class);
	}

	public function testProcessAppsNotRegistered(): void {
		$request = $this->createMock(IRequest::class);
		$this->expectException(RuntimeException::class);

		$this->manager->process('webfinger', $request);
	}

	public function testProcessNoHandlersRegistered(): void {
		$request = $this->createMock(IRequest::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects(self::once())
			->method('getWellKnownHandlers')
			->willReturn([]);

		$response = $this->manager->process('webfinger', $request);

		self::assertNull($response);
	}

	public function testProcessHandlerNotLoadable(): void {
		$request = $this->createMock(IRequest::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$handler = new class {
		};
		$registrationContext->expects(self::once())
			->method('getWellKnownHandlers')
			->willReturn([
				new ServiceRegistration('test', get_class($handler)),
			]);
		$this->mocks[ContainerInterface::class]->expects(self::once())
			->method('get')
			->with(get_class($handler))
			->willThrowException(new QueryException(''));
		$this->mocks[LoggerInterface::class]->expects(self::once())
			->method('error');

		$response = $this->manager->process('webfinger', $request);

		self::assertNull($response);
	}

	public function testProcessHandlerOfWrongType(): void {
		$request = $this->createMock(IRequest::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$handler = new class {
		};
		$registrationContext->expects(self::once())
			->method('getWellKnownHandlers')
			->willReturn([
				new ServiceRegistration('test', get_class($handler)),
			]);
		$this->mocks[ContainerInterface::class]->expects(self::once())
			->method('get')
			->with(get_class($handler))
			->willReturn($handler);
		$this->mocks[LoggerInterface::class]->expects(self::once())
			->method('error');

		$response = $this->manager->process('webfinger', $request);

		self::assertNull($response);
	}

	public function testProcess(): void {
		$request = $this->createMock(IRequest::class);
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$handler = new class implements IHandler {
			#[\Override]
			public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
				return (new JrdResponse($service))->addAlias('alias');
			}
		};
		$registrationContext->expects(self::once())
			->method('getWellKnownHandlers')
			->willReturn([
				new ServiceRegistration('test', get_class($handler)),
			]);
		$this->mocks[ContainerInterface::class]->expects(self::once())
			->method('get')
			->with(get_class($handler))
			->willReturn($handler);

		$response = $this->manager->process('webfinger', $request);

		self::assertNotNull($response);
		self::assertInstanceOf(JrdResponse::class, $response);
	}
}
