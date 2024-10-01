<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;

/**
 * @template-extends QBMapper<WebhookListener>
 */
class WebhookListenerMapper extends QBMapper {
	public const TABLE_NAME = 'webhook_listeners';

	private const EVENTS_CACHE_KEY_PREFIX = 'eventsUsedInWebhooks';

	private ?ICache $cache = null;

	public function __construct(
		IDBConnection $db,
		ICacheFactory $cacheFactory,
	) {
		parent::__construct($db, self::TABLE_NAME, WebhookListener::class);
		if ($cacheFactory->isAvailable()) {
			$this->cache = $cacheFactory->createDistributed();
		}
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function getById(int $id): WebhookListener {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return $this->findEntity($qb);
	}

	/**
	 * @throws Exception
	 * @return WebhookListener[]
	 */
	public function getAll(): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName());

		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function addWebhookListener(
		?string $appId,
		?string $userId,
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?string $userIdFilter,
		?array $headers,
		AuthMethod $authMethod,
		#[\SensitiveParameter]
		?array $authData,
	): WebhookListener {
		/* Remove any superfluous antislash */
		$event = ltrim($event, '\\');
		if (!class_exists($event) || !is_a($event, IWebhookCompatibleEvent::class, true)) {
			throw new \UnexpectedValueException("$event is not an event class compatible with webhooks");
		}
		$webhookListener = WebhookListener::fromParams(
			[
				'appId' => $appId,
				'userId' => $userId,
				'httpMethod' => $httpMethod,
				'uri' => $uri,
				'event' => $event,
				'eventFilter' => $eventFilter ?? [],
				'userIdFilter' => $userIdFilter ?? '',
				'headers' => $headers,
				'authMethod' => $authMethod->value,
			]
		);
		$webhookListener->setAuthDataClear($authData);
		$this->cache?->remove($this->buildCacheKey($userIdFilter));
		return $this->insert($webhookListener);
	}

	/**
	 * @throws Exception
	 */
	public function updateWebhookListener(
		int $id,
		?string $appId,
		?string $userId,
		string $httpMethod,
		string $uri,
		string $event,
		?array $eventFilter,
		?string $userIdFilter,
		?array $headers,
		AuthMethod $authMethod,
		#[\SensitiveParameter]
		?array $authData,
	): WebhookListener {
		/* Remove any superfluous antislash */
		$event = ltrim($event, '\\');
		if (!class_exists($event) || !is_a($event, IWebhookCompatibleEvent::class, true)) {
			throw new \UnexpectedValueException("$event is not an event class compatible with webhooks");
		}
		$webhookListener = WebhookListener::fromParams(
			[
				'id' => $id,
				'appId' => $appId,
				'userId' => $userId,
				'httpMethod' => $httpMethod,
				'uri' => $uri,
				'event' => $event,
				'eventFilter' => $eventFilter ?? [],
				'userIdFilter' => $userIdFilter ?? '',
				'headers' => $headers,
				'authMethod' => $authMethod->value,
			]
		);
		$webhookListener->setAuthDataClear($authData);
		$this->cache?->remove($this->buildCacheKey($userIdFilter));
		return $this->update($webhookListener);
	}

	/**
	 * @throws Exception
	 */
	public function deleteById(int $id): bool {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));

		return ($qb->executeStatement() > 0);
	}

	/**
	 * Delete all registrations made by the given appId
	 *
	 * @throws Exception
	 * @return int number of registration deleted
	 */
	public function deleteByAppId(string $appId): int {
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('app_id', $qb->createNamedParameter($appId, IQueryBuilder::PARAM_STR)));

		return $qb->executeStatement();
	}

	/**
	 * @throws Exception
	 * @return list<string>
	 */
	private function getAllConfiguredEventsFromDatabase(string $userId): array {
		$qb = $this->db->getQueryBuilder();

		$qb->selectDistinct('event')
			->from($this->getTableName())
			->where($qb->expr()->emptyString('user_id_filter'));

		if ($userId !== '') {
			$qb->orWhere($qb->expr()->eq('user_id_filter', $qb->createNamedParameter($userId)));
		}

		$result = $qb->executeQuery();

		$configuredEvents = [];

		while (($event = $result->fetchOne()) !== false) {
			$configuredEvents[] = $event;
		}

		return $configuredEvents;
	}

	/**
	 * List all events with at least one webhook configured, with cache
	 * @throws Exception
	 * @return list<string>
	 */
	public function getAllConfiguredEvents(?string $userId = null): array {
		$cacheKey = $this->buildCacheKey($userId);
		$events = $this->cache?->get($cacheKey);
		if ($events !== null) {
			return json_decode($events);
		}
		$events = $this->getAllConfiguredEventsFromDatabase($userId ?? '');
		// cache for 5 minutes
		$this->cache?->set($cacheKey, json_encode($events), 300);
		return $events;
	}

	/**
	 * @throws Exception
	 */
	public function getByEvent(string $event, ?string $userId = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('event', $qb->createNamedParameter($event, IQueryBuilder::PARAM_STR)));


		if ($userId === '' || $userId === null) {
			$qb->andWhere($qb->expr()->emptyString('user_id_filter'));
		} else {
			$qb->andWhere(
				$qb->expr()->orX(
					$qb->expr()->emptyString('user_id_filter'),
					$qb->expr()->eq('user_id_filter', $qb->createNamedParameter($userId)),
				)
			);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function getByUri(string $uri): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('uri', $qb->createNamedParameter($uri, IQueryBuilder::PARAM_STR)));

		return $this->findEntities($qb);
	}

	private function buildCacheKey(?string $userIdFilter): string {
		return self::EVENTS_CACHE_KEY_PREFIX . '_' . ($userIdFilter ?? '');
	}
}
