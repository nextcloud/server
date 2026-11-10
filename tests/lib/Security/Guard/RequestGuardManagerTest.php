<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Guard;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Security\Guard\RequestGuardManager;
use OCP\IRequest;
use OCP\Security\Guard\GuardDecision;
use OCP\Security\Guard\IRequestGuard;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

final class RequestGuardManagerTest extends TestCase {
	private RequestGuardManager $manager;
	private IRequest $request;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->createInstanceWithMocks(RequestGuardManager::class);
		$this->request = $this->createMock(IRequest::class);
	}

	/**
	 * @param array<string, IRequestGuard> $guards class name => guard
	 */
	private function withRegisteredGuards(array $guards): void {
		$registrations = [];
		foreach (array_keys($guards) as $class) {
			$registrations[] = new ServiceRegistration('testing', $class);
		}

		$context = $this->createMock(RegistrationContext::class);
		$context->method('getRequestGuards')
			->willReturn($registrations);
		$this->getAutoMock(Coordinator::class)->method('getRegistrationContext')
			->willReturn($context);
		$this->getAutoMock(ContainerInterface::class)->method('get')
			->willReturnCallback(static fn (string $class): IRequestGuard => $guards[$class]);
	}

	private function guard(?GuardDecision $decision): IRequestGuard {
		$guard = $this->createMock(IRequestGuard::class);
		$guard->method('check')
			->with($this->request)
			->willReturn($decision);
		return $guard;
	}

	public function testNoGuardsLetTheRequestPass(): void {
		$this->withRegisteredGuards([]);

		$this->assertNull($this->manager->check($this->request));
	}

	public function testNoRegistrationContextLetsTheRequestPass(): void {
		$this->getAutoMock(Coordinator::class)->method('getRegistrationContext')
			->willReturn(null);

		$this->assertNull($this->manager->check($this->request));
	}

	public function testGuardsThatAllowLetTheRequestPass(): void {
		$this->withRegisteredGuards([
			'GuardA' => $this->guard(null),
			'GuardB' => $this->guard(null),
		]);

		$this->assertNull($this->manager->check($this->request));
	}

	public function testFirstRefusalWins(): void {
		$first = GuardDecision::deny(3600, 'FIRST');
		$last = $this->createMock(IRequestGuard::class);
		$last->expects(self::never())->method('check');
		$this->withRegisteredGuards([
			'GuardA' => $this->guard(null),
			'GuardB' => $this->guard($first),
			'GuardC' => $last,
		]);

		$this->assertSame($first, $this->manager->check($this->request));
	}

	public function testThrowingGuardIsLoggedAndSkipped(): void {
		$throwing = $this->createMock(IRequestGuard::class);
		$throwing->method('check')
			->willThrowException(new \RuntimeException('boom'));
		$decision = GuardDecision::deny(60, 'REF');
		$this->withRegisteredGuards([
			'Throwing' => $throwing,
			'Refusing' => $this->guard($decision),
		]);
		$this->getAutoMock(LoggerInterface::class)->expects(self::once())
			->method('error');

		$this->assertSame($decision, $this->manager->check($this->request));
	}

	public function testDecisionKeepsItsValues(): void {
		$decision = GuardDecision::deny(14400, 'K7Q2MPXA');

		$this->assertSame(14400, $decision->getRetryAfter());
		$this->assertSame('K7Q2MPXA', $decision->getReference());
	}

	public function testNegativeRetryAfterIsClamped(): void {
		$this->assertSame(0, GuardDecision::deny(-5, 'REF')->getRetryAfter());
	}
}
