<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue\Fixtures;

use OCP\IUserSession;
use OCP\MessageQueue\Attribute\AsMessageHandler;
use OCP\MessageQueue\Exception\RecoverableMessageException;
use OCP\MessageQueue\Exception\UnrecoverableMessageException;

final class MultiHandler {
	/** @var list<string|null> */
	public array $users = [];

	/** @var list<LowMessage> */
	public array $low = [];

	public function __construct(
		private readonly IUserSession $userSession,
	) {
	}

	#[AsMessageHandler]
	public function user(UserMessage $message): void {
		$this->users[] = $this->userSession->getUser()?->getUID();
	}

	#[AsMessageHandler]
	public function fail(FailingMessage $message): void {
		if ($message->unrecoverable) {
			throw new UnrecoverableMessageException('Permanent failure');
		}

		if ($message->recoverable) {
			throw new RecoverableMessageException('Service unavailable');
		}

		throw new \RuntimeException('Temporary failure');
	}

	#[AsMessageHandler]
	public function low(LowMessage $message): void {
		$this->low[] = $message;
	}

	public function notAHandler(TestMessage $message): void {
	}
}
