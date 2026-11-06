<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Token;

use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Exceptions\WipeTokenException;
use OC\Authentication\Token\IProvider as ITokenProvider;
use OC\Authentication\Token\IToken;
use OC\Authentication\Token\IWipeableToken;
use OC\Authentication\Token\RemoteWipe;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RemoteWipeTest extends TestCase {
	/** @var RemoteWipe */
	private $remoteWipe;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->remoteWipe = $this->createInstanceWithMocks(RemoteWipe::class);
	}

	public function testMarkNonWipableTokenForWipe(): void {
		$token = $this->createMock(IToken::class);
		$result = $this->remoteWipe->markTokenForWipe($token);
		$this->assertFalse($result);
	}

	public function testMarkTokenForWipe(): void {
		$token = $this->createMock(IWipeableToken::class);

		// The provider marks the other tokens of its login as well
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('markForWipe')
			->with($token);

		$result = $this->remoteWipe->markTokenForWipe($token);
		$this->assertTrue($result);
	}

	public function testMarkAllTokensForWipeNoWipeableToken(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$token1 = $this->createMock(IToken::class);
		$token2 = $this->createMock(IToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getLoginsOfUser')
			->with('user123')
			->willReturn([1 => $token1, 2 => $token2]);
		$this->getAutoMock(ITokenProvider::class)->expects($this->never())
			->method('markForWipe');

		$result = $this->remoteWipe->markAllTokensForWipe($user);

		$this->assertFalse($result);
	}

	public function testMarkAllTokensForWipe(): void {
		/** @var IUser|MockObject $user */
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user123');
		$token1 = $this->createMock(IToken::class);
		$token2 = $this->createMock(IWipeableToken::class);
		$token3 = $this->createMock(IWipeableToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getLoginsOfUser')
			->with('user123')
			->willReturn([1 => $token1, 2 => $token2, 3 => $token3]);
		// Once per login, which marks the other tokens of the login as well
		$marked = [];
		$this->getAutoMock(ITokenProvider::class)->expects($this->exactly(2))
			->method('markForWipe')
			->willReturnCallback(function ($token) use (&$marked): void {
				$marked[] = $token;
			});

		$result = $this->remoteWipe->markAllTokensForWipe($user);

		$this->assertTrue($result);
		$this->assertSame([$token2, $token3], $marked);
	}

	public function testStartWipingNotAWipeToken(): void {
		$token = $this->createMock(IToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getToken')
			->with('tk1')
			->willReturn($token);
		$this->getAutoMock(IEventDispatcher::class)->expects($this->never())
			->method('dispatch');

		$result = $this->remoteWipe->start('tk1');

		$this->assertFalse($result);
	}

	public function testStartWiping(): void {
		$token = $this->createMock(IToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getToken')
			->with('tk1')
			->willThrowException(new WipeTokenException($token));
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatch');
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatch')
			->with(RemoteWipeStarted::class, $this->equalTo(new RemoteWipeStarted($token)));

		$result = $this->remoteWipe->start('tk1');

		$this->assertTrue($result);
	}

	public function testFinishWipingNotAWipeToken(): void {
		$token = $this->createMock(IToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getToken')
			->with('tk1')
			->willReturn($token);
		$this->getAutoMock(IEventDispatcher::class)->expects($this->never())
			->method('dispatch');

		$result = $this->remoteWipe->finish('tk1');

		$this->assertFalse($result);
	}

	public function startFinishWiping() {
		$token = $this->createMock(IToken::class);
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getToken')
			->with('tk1')
			->willThrowException(new WipeTokenException($token));
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatch');
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('invalidateToken')
			->with($token);
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatch')
			->with(RemoteWipeFinished::class, $this->equalTo(new RemoteWipeFinished($token)));

		$result = $this->remoteWipe->finish('tk1');

		$this->assertTrue($result);
	}

	public function testFinishWiping(): void {
		$token = $this->createMock(IToken::class);
		$token->method('getId')->willReturn(42);
		$token->method('getUID')->willReturn('user1');
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('getToken')
			->with('tk1')
			->willThrowException(new WipeTokenException($token));
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('invalidateToken')
			->with('tk1');
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatch')
			->with(RemoteWipeFinished::class, $this->equalTo(new RemoteWipeFinished($token)));
		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'auth.remote_wipe.finished'
				&& $event->getParameters() === ['tokenId' => 42, 'userId' => 'user1']));

		$this->assertTrue($this->remoteWipe->finish('tk1'));
	}
}
