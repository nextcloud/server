<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Authentication\Token;

use OC\Authentication\Events\TokensInvalidationFinished;
use OC\Authentication\Events\TokensInvalidationStarted;
use OC\Authentication\Token\Invalidator;
use OC\Authentication\Token\IProvider as ITokenProvider;
use OC\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class InvalidatorTest extends TestCase {
	/** @var ITokenProvider|MockObject */
	private $tokenProvider;

	/** @var IEventDispatcher|MockObject */
	private $eventDispatcher;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var Invalidator */
	private $invalidator;

	protected function setUp(): void {
		parent::setUp();

		$this->tokenProvider = $this->createMock(ITokenProvider::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->invalidator = new Invalidator(
			$this->tokenProvider,
			$this->eventDispatcher,
			$this->logger
		);
	}

	public function testInvalidateAllUserTokens(): void {
		$uid = 'user123';
		$tokens = [
			$this->createMock(IToken::class),
			$this->createMock(IToken::class),
		];

		$tokens[0]->method('getId')->willReturn(1111);
		$tokens[1]->method('getId')->willReturn(2222);

		$this->tokenProvider
			->expects($this->once())
			->method('getTokenByUser')
			->with($uid)
			->willReturn($tokens);

		$dispatchTypedInvokedCount = $this->exactly(2);
		$this->eventDispatcher
			->expects($dispatchTypedInvokedCount)
			->method('dispatchTyped')
			->willReturnCallback(function (Event $event) use ($uid, $dispatchTypedInvokedCount) {
				if ($dispatchTypedInvokedCount->getInvocationCount() === 1) {
					$this->assertInstanceOf(TokensInvalidationStarted::class, $event);
					$this->assertSame($uid, $event->getUserId());
				} elseif ($dispatchTypedInvokedCount->getInvocationCount() === 2) {
					$this->assertInstanceOf(TokensInvalidationFinished::class, $event);
					$this->assertSame($uid, $event->getUserId());
				}
			});

		$invokedCount = $this->exactly(2);
		$this->tokenProvider
			->expects($invokedCount)
			->method('invalidateTokenById')
			->willReturnCallback(function ($uid, $tokenId) use ($invokedCount) {
				if ($invokedCount->getInvocationCount() === 1) {
					$this->assertSame(['user123', 1111], [$uid, $tokenId]);
				} elseif ($invokedCount->getInvocationCount() === 2) {
					$this->assertSame(['user123', 2222], [$uid, $tokenId]);
				}
			});

		$this->logger
			->expects($this->once())
			->method('info')
			->with("Invalidating all tokens for user: $uid");

		$result = $this->invalidator->invalidateAllUserTokens($uid);

		$this->assertTrue($result);
	}
}
