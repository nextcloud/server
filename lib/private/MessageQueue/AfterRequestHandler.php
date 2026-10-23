<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\Files\ISetupManager;
use OCP\IConfig;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Lock\ILockingProvider;
use OCP\MessageQueue\Queue;
use Psr\Log\LoggerInterface;

/**
 * Handles the messages dispatched during a web request once the response has
 * been sent to the client, using fastcgi_finish_request(). Messages that are
 * not handled within the budget stay in the queue for cron and workers.
 */
class AfterRequestHandler {
	private const int TIME_LIMIT = 10;

	private const int MESSAGE_LIMIT = 5;

	/** @var list<string> */
	private array $messageIds = [];

	private bool $registered = false;

	public function __construct(
		private readonly Consumer $consumer,
		private readonly IConfig $config,
		private readonly ISession $session,
		private readonly IUserSession $userSession,
		private readonly ISetupManager $setupManager,
		private readonly ILockingProvider $lockingProvider,
		private readonly LoggerInterface $logger,
		private readonly bool $isCLI,
	) {
	}

	public function add(Queue $queue, string $messageId): void {
		if ($queue === Queue::Low || !$this->isEnabled()) {
			return;
		}

		$this->messageIds[] = $messageId;
		if (!$this->registered) {
			$this->registered = true;
			$this->registerShutdownFunction($this->run(...));
		}
	}

	public function run(): void {
		$messageIds = $this->messageIds;
		$this->messageIds = [];
		if ($messageIds === []) {
			return;
		}

		try {
			$this->session->close();
			$this->finishRequest();

			$this->userSession->setVolatileActiveUser(null);
			$this->setupManager->tearDown();
			$this->consumer->consumeMessages($messageIds, self::TIME_LIMIT, self::MESSAGE_LIMIT);
		} catch (\Throwable $throwable) {
			$this->logger->error('Error while handling messages after the request: ' . $throwable->getMessage(), ['exception' => $throwable]);
		} finally {
			// The shutdown functions registered during boot already ran
			$this->lockingProvider->releaseAll();
		}
	}

	protected function isEnabled(): bool {
		return !$this->isCLI
			&& $this->canFinishRequest()
			&& $this->config->getSystemValueBool('message_queue.handle_after_request', true);
	}

	protected function canFinishRequest(): bool {
		return function_exists('fastcgi_finish_request');
	}

	protected function registerShutdownFunction(callable $callback): void {
		register_shutdown_function($callback);
	}

	protected function finishRequest(): void {
		fastcgi_finish_request();
	}
}
