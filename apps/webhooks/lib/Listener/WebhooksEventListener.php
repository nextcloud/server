<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks\Listener;

use OCA\Webhooks\BackgroundJobs\WebhookCall;
use OCA\Webhooks\Db\WebhookListenerMapper;
use OCA\Webhooks\Service\PHPMongoQuery;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\JsonSerializer;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The class to handle the share events
 * @template-implements IEventListener<Event>
 */
class WebhooksEventListener implements IEventListener {
	public function __construct(
		private WebhookListenerMapper $mapper,
		private IJobList $jobList,
		private LoggerInterface $logger,
		private IUserSession $userSession,
	) {
	}

	public function handle(Event $event): void {
		$webhookListeners = $this->mapper->getByEvent($event::class);
		$user = $this->userSession->getUser();

		foreach ($webhookListeners as $webhookListener) {
			// TODO add group membership to be able to filter on it
			$data = [
				'event' => $this->serializeEvent($event),
				'user' => (is_null($user) ? null : JsonSerializer::serializeUser($user)),
				'time' => time(),
			];
			if ($this->filterMatch($webhookListener->getEventFilter(), $data)) {
				$this->jobList->add(
					WebhookCall::class,
					[
						$data,
						$webhookListener->getId(),
					]
				);
			}
		}
	}

	private function serializeEvent(Event $event): array|\JsonSerializable {
		if ($event instanceof \JsonSerializable) {
			return $event;
		} else {
			/* Event is not serializable, we fallback to reflection to still send something */
			$data = ['class' => $event::class];
			$ref = new \ReflectionClass($event);
			foreach ($ref->getMethods() as $method) {
				if (str_starts_with($method->getName(), 'get')) {
					$key = strtolower(substr($method->getName(), 3));
					$value = $method->invoke($event);
					if ($value instanceof \OCP\Files\FileInfo) {
						$value = [
							'id' => $value->getId(),
							'path' => $value->getPath(),
						];
					}
					$data[$key] = $value;
				}
			}
			$this->logger->debug('Webhook had to use fallback to serialize event '.$event::class);
			return $data;
		}
	}

	private function filterMatch(array $filter, array $data): bool {
		if ($filter === []) {
			return true;
		}
		return PHPMongoQuery::executeQuery($filter, $data);
	}
}
