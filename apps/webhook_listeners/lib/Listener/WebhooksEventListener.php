<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Listener;

use OCA\WebhookListeners\BackgroundJobs\WebhookCall;
use OCA\WebhookListeners\Db\WebhookListenerMapper;
use OCA\WebhookListeners\Service\PHPMongoQuery;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\EventDispatcher\JsonSerializer;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * The class to handle the share events
 * @template-implements IEventListener<IWebhookCompatibleEvent&Event>
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
		$user = $this->userSession->getUser();
		$webhookListeners = $this->mapper->getByEvent($event::class, $user?->getUID());

		foreach ($webhookListeners as $webhookListener) {
			// TODO add group membership to be able to filter on it
			$data = [
				'event' => $this->serializeEvent($event),
				/* Do not remove 'user' from here, see BackgroundJobs/WebhookCall.php */
				'user' => (is_null($user) ? null : JsonSerializer::serializeUser($user)),
				'time' => time(),
			];
			if ($this->filterMatch($webhookListener->getEventFilter(), $data)) {
				$this->jobList->add(
					WebhookCall::class,
					[
						$data,
						$webhookListener->getId(),
						/* Random string to avoid collision with another job with the same parameters */
						bin2hex(random_bytes(5)),
					]
				);
			}
		}
	}

	private function serializeEvent(IWebhookCompatibleEvent $event): array {
		$data = $event->getWebhookSerializable();
		$data['class'] = $event::class;
		return $data;
	}

	private function filterMatch(array $filter, array $data): bool {
		if ($filter === []) {
			return true;
		}
		return PHPMongoQuery::executeQuery($filter, $data);
	}
}
