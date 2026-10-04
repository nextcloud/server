<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\Files\ISetupManager;
use OCP\IDBConnection;
use OCP\ITempManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\MessageQueue\Exception\InvalidMessageException;
use OCP\MessageQueue\Exception\RecoverableMessageException;
use OCP\MessageQueue\Exception\UnrecoverableMessageException;
use OCP\MessageQueue\Queue;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Consumes messages from the given queues, highest priority first.
 *
 * @psalm-suppress ClassMustBeFinal For unit tests
 */
class Consumer {
	private const int MAX_RETRY_DELAY = 86400;

	private bool $shouldStop = false;

	public function __construct(
		private readonly MessageStore $store,
		private readonly HandlerRegistry $handlerRegistry,
		private readonly MessageMetadataReader $metadataReader,
		private readonly MessageNormalizer $normalizer,
		private readonly ContainerInterface $container,
		private readonly IUserManager $userManager,
		private readonly IUserSession $userSession,
		private readonly ISetupManager $setupManager,
		private readonly IDBConnection $connection,
		private readonly ITempManager $tempManager,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @param list<Queue> $queues
	 * @param int|null $timeLimit Stop after this many seconds
	 * @param int|null $messageLimit Stop after handling this many messages
	 * @param int|null $memoryLimit Stop when the memory usage exceeds this many bytes
	 * @param bool $stopWhenEmpty Stop as soon as all queues are empty instead of waiting for new messages
	 * @param int $sleep Seconds to wait before polling again when all queues are empty
	 * @param (callable(string): void)|null $output Receives progress messages
	 */
	public function consume(
		array $queues = [Queue::High, Queue::Default, Queue::Low],
		?int $timeLimit = null,
		?int $messageLimit = null,
		?int $memoryLimit = null,
		bool $stopWhenEmpty = false,
		int $sleep = 1,
		?callable $output = null,
	): void {
		$next = function () use ($queues): ?QueuedMessage {
			foreach ($queues as $queue) {
				$message = $this->store->claimNext($queue);
				if ($message instanceof QueuedMessage) {
					return $message;
				}
			}

			return null;
		};
		$this->run($next, $timeLimit, $messageLimit, $memoryLimit, $stopWhenEmpty, $sleep, $output);
	}

	/**
	 * Handles the given messages in order, if they are still available.
	 *
	 * @param list<string> $messageIds
	 */
	public function consumeMessages(array $messageIds, ?int $timeLimit = null, ?int $messageLimit = null): void {
		$next = function () use (&$messageIds): ?QueuedMessage {
			while (($id = array_shift($messageIds)) !== null) {
				$message = $this->store->claimById($id);
				if ($message instanceof QueuedMessage) {
					return $message;
				}
			}

			return null;
		};
		$this->run($next, $timeLimit, $messageLimit, stopWhenEmpty: true);
	}

	/**
	 * Stops consuming after the current message.
	 */
	public function stop(): void {
		$this->shouldStop = true;
	}

	/**
	 * @param \Closure(): ?QueuedMessage $next
	 * @param (callable(string): void)|null $output
	 */
	private function run(
		\Closure $next,
		?int $timeLimit = null,
		?int $messageLimit = null,
		?int $memoryLimit = null,
		bool $stopWhenEmpty = false,
		int $sleep = 1,
		?callable $output = null,
	): void {
		$endTime = $timeLimit !== null ? microtime(true) + (float)$timeLimit : null;
		$handled = 0;

		try {
			while (!$this->shouldStop) {
				if ($endTime !== null && microtime(true) >= $endTime) {
					break;
				}

				$message = $next();
				if ($message instanceof QueuedMessage) {
					$this->handle($message, $output);
					++$handled;
					if ($messageLimit !== null && $handled >= $messageLimit) {
						break;
					}

					if ($memoryLimit !== null && memory_get_usage(true) > $memoryLimit) {
						break;
					}
				} elseif ($stopWhenEmpty) {
					break;
				} else {
					sleep($sleep);
				}

				$this->dispatchSignals();
			}
		} finally {
			$this->shouldStop = false;
		}
	}

	/**
	 * Runs the signal handlers, which may call stop().
	 */
	private function dispatchSignals(): void {
		if (function_exists('pcntl_signal_dispatch')) {
			pcntl_signal_dispatch();
		}
	}

	/**
	 * @param (callable(string): void)|null $output
	 */
	private function handle(QueuedMessage $queued, ?callable $output): void {
		if ($output !== null) {
			$output('Handling ' . $queued->messageClass . ' (' . $queued->id . ')');
		}

		try {
			$this->invokeHandler($queued);
			$error = null;
		} catch (\Throwable $throwable) {
			$error = $throwable;
		}

		// Before writing to the store, so a transaction left open by the handler can't roll it back
		$this->cleanUp();

		if (!$error instanceof \Throwable) {
			$this->store->delete($queued->id);
			return;
		}

		$delay = $this->getRetryDelay($queued, $error);
		if ($delay !== null) {
			$this->logger->warning('Error while handling message ' . $queued->messageClass . ', retrying in ' . $delay . ' seconds: ' . $error->getMessage(), ['exception' => $error]);
			$this->store->retry($queued->id, $delay);
		} else {
			$this->logger->error('Error while handling message ' . $queued->messageClass . ', moving it to the failed queue: ' . $error->getMessage(), ['exception' => $error]);
			$this->store->fail($queued->id, $error::class . ': ' . $error->getMessage());
		}

		if ($output !== null) {
			$output('Failed ' . $queued->messageClass . ': ' . $error->getMessage() . ($delay !== null ? ' (will retry)' : ''));
		}
	}

	private function invokeHandler(QueuedMessage $queued): void {
		$class = $queued->messageClass;
		$handler = $this->handlerRegistry->getHandler($class);
		if ($handler === null || !$this->metadataReader->isMessage($class)) {
			throw new UnrecoverableMessageException('No handler registered for message ' . $class);
		}

		try {
			$data = json_decode($queued->body, true, flags: JSON_THROW_ON_ERROR);
			if (!is_array($data)) {
				throw new InvalidMessageException('Message body is not an object');
			}

			$message = $this->normalizer->denormalize($class, $data);
		} catch (\JsonException|InvalidMessageException $e) {
			throw new UnrecoverableMessageException('Could not decode message ' . $class . ': ' . $e->getMessage(), $e->getCode(), previous: $e);
		}

		$userProperty = $this->metadataReader->get($class)->userProperty;
		$user = null;
		if ($userProperty !== null) {
			/** @var mixed $userId */
			$userId = $message->$userProperty;
			$user = is_string($userId) ? $this->userManager->get($userId) : null;
			if (!$user instanceof IUser) {
				throw new UnrecoverableMessageException('User ' . var_export($userId, true) . ' of message ' . $class . ' does not exist');
			}

		}

		try {
			if ($user instanceof IUser) {
				$this->userSession->setVolatileActiveUser($user);
				$this->setupManager->setupForUser($user);
			}

			$callable = [$this->container->get($handler['class']), $handler['method']];
			if (!is_callable($callable)) {
				throw new UnrecoverableMessageException('Message handler ' . $handler['class'] . '::' . $handler['method'] . ' is not callable');
			}

			$callable($message);
		} finally {
			$this->setupManager->tearDown();
			$this->userSession->setVolatileActiveUser(null);
		}
	}

	/**
	 * @return int|null Seconds to wait before retrying, null if the message should not be retried
	 */
	private function getRetryDelay(QueuedMessage $queued, \Throwable $error): ?int {
		if ($error instanceof UnrecoverableMessageException) {
			return null;
		}

		$metadata = $this->metadataReader->get($queued->messageClass);
		if (!$error instanceof RecoverableMessageException && $queued->retryCount >= $metadata->maxRetries) {
			return null;
		}

		$delay = (float)$metadata->retryDelay * ($metadata->retryMultiplier ** (float)$queued->retryCount);
		return (int)min((float)self::MAX_RETRY_DELAY, $delay);
	}

	private function cleanUp(): void {
		$this->tempManager->clean();
		if ($this->connection->inTransaction()) {
			$this->connection->rollBack();
			$this->logger->warning('A message handler left a transaction open, it was rolled back');
		}
	}
}
