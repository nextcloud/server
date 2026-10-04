<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OC\MessageQueue;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\DB\QueryBuilder\ConflictResolutionMode;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\MessageQueue\Queue;
use OCP\Snowflake\ISnowflakeGenerator;

/**
 * Stores messages in the message_queue table.
 */
final readonly class MessageStore {
	public const string TABLE = 'message_queue';

	private const string FAILED_QUEUE = 'failed';

	/** Seconds after which a message claimed by a crashed worker is delivered again */
	private const int REDELIVER_TIMEOUT = 3600;

	private const int MAX_CLAIM_ATTEMPTS = 5;

	public function __construct(
		private IDBConnection $connection,
		private ITimeFactory $timeFactory,
		private ISnowflakeGenerator $snowflakeGenerator,
	) {
	}

	/**
	 * @param string|null $deduplicationHash Skips the insert while a message with this hash is pending
	 * @return string|null Id of the stored message, null if it was deduplicated
	 */
	public function add(Queue $queue, string $messageClass, string $body, ?string $deduplicationHash): ?string {
		if ($deduplicationHash !== null && $this->hasPending($queue, $deduplicationHash)) {
			return null;
		}

		$now = $this->timeFactory->getTime();
		$id = $this->snowflakeGenerator->nextId();
		$qb = $this->connection->getQueryBuilder();
		$qb->insert(self::TABLE)
			->values([
				'id' => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
				'queue_name' => $qb->createNamedParameter($queue->value),
				'message_class' => $qb->createNamedParameter($messageClass),
				'body' => $qb->createNamedParameter($body),
				'created_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT),
				'available_at' => $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT),
				'deduplication_hash' => $qb->createNamedParameter($deduplicationHash),
			])
			->executeStatement();
		return $id;
	}

	/**
	 * Claims the next available message of the queue.
	 *
	 * SQLite doesn't support SKIP LOCKED and Oracle can't combine it with a row
	 * limit, so they claim optimistically and retry when another worker was faster.
	 */
	public function claimNext(Queue $queue): ?QueuedMessage {
		$provider = $this->connection->getDatabaseProvider();
		if ($provider === IDBConnection::PLATFORM_SQLITE || $provider === IDBConnection::PLATFORM_ORACLE) {
			return $this->claimNextOptimistically($queue);
		}

		return $this->claimNextWithSkipLocked($queue);
	}

	/**
	 * Locks the next available message, skipping the ones locked by other workers.
	 */
	private function claimNextWithSkipLocked(Queue $queue): ?QueuedMessage {
		$this->connection->beginTransaction();
		try {
			$message = $this->fetchAvailable($queue, skipLocked: true);
			$claimed = $message instanceof QueuedMessage && $this->claim($message);
			$this->connection->commit();
		} catch (\Throwable $throwable) {
			$this->connection->rollBack();
			throw $throwable;
		}

		return $claimed ? $message : null;
	}

	private function claimNextOptimistically(Queue $queue): ?QueuedMessage {
		for ($attempt = 0; $attempt < self::MAX_CLAIM_ATTEMPTS; ++$attempt) {
			$message = $this->fetchAvailable($queue);
			if (!$message instanceof QueuedMessage) {
				return null;
			}

			if ($this->claim($message)) {
				return $message;
			}
		}

		return null;
	}

	/**
	 * Claims a specific message, if it is available.
	 */
	public function claimById(string $id): ?QueuedMessage {
		$message = $this->fetchAvailable(id: $id);
		return $message instanceof QueuedMessage && $this->claim($message) ? $message : null;
	}

	public function delete(string $id): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->delete(self::TABLE)
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	/**
	 * Makes a claimed message available again after the given delay.
	 */
	public function retry(string $id, int $delay): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE)
			->set('retry_count', $qb->func()->add('retry_count', $qb->createNamedParameter(1, IQueryBuilder::PARAM_INT)))
			->set('available_at', $qb->createNamedParameter($this->timeFactory->getTime() + $delay, IQueryBuilder::PARAM_INT))
			->set('delivered_at', $qb->createNamedParameter(null))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	/**
	 * Moves a claimed message to the failed queue.
	 */
	public function fail(string $id, string $error): void {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE)
			->set('queue_name', $qb->createNamedParameter(self::FAILED_QUEUE))
			->set('last_error', $qb->createNamedParameter($error))
			->set('delivered_at', $qb->createNamedParameter(null))
			->set('deduplication_hash', $qb->createNamedParameter(null))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
			->executeStatement();
	}

	private function fetchAvailable(?Queue $queue = null, ?string $id = null, bool $skipLocked = false): ?QueuedMessage {
		$now = $this->timeFactory->getTime();
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id', 'message_class', 'body', 'retry_count', 'delivered_at')
			->from(self::TABLE)
			->where($qb->expr()->lte('available_at', $qb->createNamedParameter($now, IQueryBuilder::PARAM_INT)))
			->andWhere($qb->expr()->orX(
				$qb->expr()->isNull('delivered_at'),
				$qb->expr()->lt('delivered_at', $qb->createNamedParameter($now - self::REDELIVER_TIMEOUT, IQueryBuilder::PARAM_INT)),
			))
			->orderBy('available_at', 'ASC')
			->addOrderBy('id', 'ASC')
			->setMaxResults(1);
		if ($queue instanceof Queue) {
			$qb->andWhere($qb->expr()->eq('queue_name', $qb->createNamedParameter($queue->value)));
		}

		if ($id !== null) {
			$qb->andWhere($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
		}

		if ($skipLocked) {
			$qb->forUpdate(ConflictResolutionMode::SkipLocked);
		}

		$result = $qb->executeQuery();
		$row = $result->fetchAssociative();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		return new QueuedMessage(
			(string)$row['id'],
			(string)$row['message_class'],
			(string)$row['body'],
			(int)$row['retry_count'],
			$row['delivered_at'] === null ? null : (int)$row['delivered_at'],
		);
	}

	/**
	 * Marks the message as delivered, unless another worker claimed it first.
	 */
	private function claim(QueuedMessage $message): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->update(self::TABLE)
			->set('delivered_at', $qb->createNamedParameter($this->timeFactory->getTime(), IQueryBuilder::PARAM_INT))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($message->id, IQueryBuilder::PARAM_INT)));
		if ($message->deliveredAt === null) {
			$qb->andWhere($qb->expr()->isNull('delivered_at'));
		} else {
			$qb->andWhere($qb->expr()->eq('delivered_at', $qb->createNamedParameter($message->deliveredAt, IQueryBuilder::PARAM_INT)));
		}

		return $qb->executeStatement() === 1;
	}

	private function hasPending(Queue $queue, string $deduplicationHash): bool {
		$qb = $this->connection->getQueryBuilder();
		$qb->select('id')
			->from(self::TABLE)
			->where($qb->expr()->eq('deduplication_hash', $qb->createNamedParameter($deduplicationHash)))
			->andWhere($qb->expr()->eq('queue_name', $qb->createNamedParameter($queue->value)))
			->andWhere($qb->expr()->isNull('delivered_at'))
			->setMaxResults(1);
		$result = $qb->executeQuery();
		$found = $result->fetchOne() !== false;
		$result->closeCursor();
		return $found;
	}
}
