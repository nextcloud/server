<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($this->createMock(RegistrationContext::class));

		self::assertFalse(
			$this->broker->hasBackend()
		);
	}

	public function testHasFaultyBackend(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects(self::once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$this->container->expects(self::once())
			->method('get')
			->willThrowException(new QueryException());
		$this->logger->expects(self::once())
			->method('error');

		self::assertFalse(
			$this->broker->hasBackend()
		);
	}

	public function testHasBackend(): void {
		$fakeTalkServiceClass = '\\OCA\\Spreed\\TalkBackend';
		$registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects(self::once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects(self::once())
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
		$this->coordinator->expects(self::once())
			->method('getRegistrationContext')
			->willReturn($registrationContext);
		$registrationContext->expects(self::once())
			->method('getTalkBackendRegistration')
			->willReturn(new ServiceRegistration('spreed', $fakeTalkServiceClass));
		$talkService = $this->createMock(ITalkBackend::class);
		$this->container->expects(self::once())
			->method('get')
			->with($fakeTalkServiceClass)
			->willReturn($talkService);
		$options = $this->createMock(IConversationOptions::class);
		$talkService->expects(self::once())
			->method('createConversation')
			->with('Watercooler', [], $options);

		$this->broker->createConversation(
			'Watercooler',
			[],
			$options
		);
	}
}
