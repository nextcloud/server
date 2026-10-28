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

	public function testIsWipePending(): void {
		$sessionToken = $this->createMock(IToken::class);
		$sessionToken->method('getType')->willReturn(IToken::TEMPORARY_TOKEN);
		$wipeToken = $this->createMock(IToken::class);
		$wipeToken->method('getType')->willReturn(IToken::WIPE_TOKEN);

		$this->assertFalse($this->remoteWipe->isWipePending([$sessionToken]));
		$this->assertTrue($this->remoteWipe->isWipePending([$sessionToken, $wipeToken]));
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
			->willReturn([1 => [$token1], 2 => [$token2]]);
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
			->willReturn([1 => [$token1], 2 => [$token2, $token3]]);
		// Once per login, which marks the other tokens of the login as well
		$this->getAutoMock(ITokenProvider::class)->expects($this->once())
			->method('markForWipe')
			->with($token2);

		$result = $this->remoteWipe->markAllTokensForWipe($user);

		$this->assertTrue($result);
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
}
