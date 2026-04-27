<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Talk;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Talk\Broker;
use OCP\AppFramework\QueryException;
use OCP\IServerContainer;
use OCP\Talk\IConversationOptions;
use OCP\Talk\ITalkBackend;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Test\TestCase;

class BrokerTest extends TestCase {
	private Coordinator $coordinator;

	private IServerContainer $container;

	private LoggerInterface $logger;

	private Broker $broker;

	protected function setUp(): void {
		parent::setUp();

		$this->coordinator = $this->createMock(Coordinator::class);
		$this->container = $this->createMock(IServerContainer::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->broker = new Broker(
			$this->coordinator,
			$this->container,
			$this->logger,
		);
	}

	public function testHasNoBackendCalledTooEarly(): void {
		$this->expectException(RuntimeException::class);

		$this->broker->hasBackend();
	}

	public function testHasNoBackend(): void {
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($this->createMock(RegistrationContext::class));

		self::assertFalse(
			$this->broker->hasBackend()
		);
	}

	public function testHasFaultyBackend(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$this->container->expects($this->once())
			->method('get')
			->willThrowException(new QueryException());
		$this->logger->expects($this->once())
			->method('error');

		self::assertFalse(
			$this->broker->hasBackend()
		);
	}

	public function testHasBackend(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects($this->once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);

		self::assertTrue(
			$this->broker->hasBackend()
		);
	}

	public function testNewConversationOptions(): void {
		$this->broker->newConversationOptions();

		// Nothing to assert
		$this->addToAssertionCount(1);
	}

	public function testCreateConversation(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects($this->once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);
		$options = $this->createMock(IConversationOptions::class);
		$talkService->expects($this->once())
			->method('createConversation')
			->with('Watercooler', [], $options);

		$this->broker->createConversation(
			'Watercooler',
			[],
			$options
		);
	}

	public function testIsEnabledForUserNoBackend(): void {
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($this->createMock(RegistrationContext::class));

		self::assertFalse(
			$this->broker->isEnabledForUser()
		);
	}

	public static function dataIsEnabledForUser(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataIsEnabledForUser')]
	public function testIsEnabledForUser(bool $enabled): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects($this->once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);
		$talkService->expects($this->once())
			->method('isEnabledForUser')
			->willReturn($enabled);

		self::assertSame(
			$enabled,
			$this->broker->isEnabledForUser()
		);
	}

	public function testIsAllowedToCreateConversationsNoBackend(): void {
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($this->createMock(RegistrationContext::class));

		self::assertFalse(
			$this->broker->isAllowedToCreateConversations()
		);
	}

	public function testIsAllowedToCreateConversationsBackendDisabled(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects($this->once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);
		$talkService->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(false);
		$talkService->expects($this->never())
			->method('isAllowedToCreateConversations');

		self::assertFalse(
			$this->broker->isAllowedToCreateConversations()
		);
	}

	public static function dataIsAllowedToCreateConversations(): array {
		return [
			[true],
			[false],
		];
	}

	#[DataProvider('dataIsAllowedToCreateConversations')]
	public function testIsAllowedToCreateConversations(bool $allowed): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects($this->once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects($this->once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects($this->once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);
		$talkService->expects($this->once())
			->method('isEnabledForUser')
			->willReturn(true);
		$talkService->expects($this->once())
			->method('isAllowedToCreateConversations')
			->willReturn($allowed);

		self::assertSame(
			$allowed,
			$this->broker->isAllowedToCreateConversations()
		);
	}
}
