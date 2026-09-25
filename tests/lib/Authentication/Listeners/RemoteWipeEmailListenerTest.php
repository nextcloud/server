<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace lib\Authentication\Listeners;

use Exception;
use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Listeners\RemoteWipeEmailListener;
use OC\Authentication\Token\IToken;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RemoteWipeEmailListenerTest extends TestCase {
	/** @var IL10N|MockObject */
	private $l10n;

	/** @var IEventListener */
	private $listener;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->listener = $this->createInstanceWithMocks(RemoteWipeEmailListener::class);

		$this->mocks[IFactory::class]->method('get')->with('core')->willReturn($this->l10n);
		$this->l10n->method('t')->willReturnArgument(0);
	}

	public function testHandleUnrelated(): void {
		$event = new Event();
		$this->mocks[IMailer::class]->expects($this->never())->method('send');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeStartedInvalidUser(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$token->method('getUID')->willReturn('nope');
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn(null);
		$this->mocks[IMailer::class]->expects($this->never())->method('send');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeStartedNoEmailSet(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn(null);
		$this->mocks[IMailer::class]->expects($this->never())->method('send');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeStartedTransmissionError(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn('user@domain.org');
		$this->mocks[IMailer::class]->expects($this->once())
			->method('send')
			->willThrowException(new Exception());
		$this->mocks[LoggerInterface::class]->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeStarted(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeStarted($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn('user@domain.org');
		$message = $this->createMock(IMessage::class);
		$this->mocks[IMailer::class]->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$message->expects($this->once())
			->method('setTo')
			->with($this->equalTo(['user@domain.org']));
		$this->mocks[IMailer::class]->expects($this->once())
			->method('send')
			->with($message);

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinishedInvalidUser(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$token->method('getUID')->willReturn('nope');
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn(null);
		$this->mocks[IMailer::class]->expects($this->never())->method('send');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinishedNoEmailSet(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn(null);
		$this->mocks[IMailer::class]->expects($this->never())->method('send');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinishedTransmissionError(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn('user@domain.org');
		$this->mocks[IMailer::class]->expects($this->once())
			->method('send')
			->willThrowException(new Exception());
		$this->mocks[LoggerInterface::class]->expects($this->once())
			->method('error');

		$this->listener->handle($event);
	}

	public function testHandleRemoteWipeFinished(): void {
		/** @var IToken|MockObject $token */
		$token = $this->createMock(IToken::class);
		$event = new RemoteWipeFinished($token);
		$token->method('getUID')->willReturn('nope');
		$user = $this->createMock(IUser::class);
		$this->mocks[IUserManager::class]->expects($this->once())
			->method('get')
			->with('nope')
			->willReturn($user);
		$user->method('getEMailAddress')->willReturn('user@domain.org');
		$message = $this->createMock(IMessage::class);
		$this->mocks[IMailer::class]->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$message->expects($this->once())
			->method('setTo')
			->with($this->equalTo(['user@domain.org']));
		$this->mocks[IMailer::class]->expects($this->once())
			->method('send')
			->with($message);

		$this->listener->handle($event);
	}
}
