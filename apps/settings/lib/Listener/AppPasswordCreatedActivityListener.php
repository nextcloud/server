<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Listener;

use BadMethodCallException;
use OC\Authentication\Events\AppPasswordCreatedEvent;
use OCA\Settings\Activity\Provider;
use OCP\Activity\IManager as IActivityManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * @template-implements IEventListener<\OC\Authentication\Events\AppPasswordCreatedEvent>
 */
class AppPasswordCreatedActivityListener implements IEventListener {
	public function __construct(
		private IActivityManager $activityManager,
		private IUserSession $userSession,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof AppPasswordCreatedEvent)) {
			return;
		}

		$activity = $this->activityManager->generateEvent();
		$activity->setApp('settings')
			->setType('security')
			->setAffectedUser($event->getToken()->getUID())
			->setAuthor($this->userSession->getUser() ? $this->userSession->getUser()->getUID() : '')
			->setSubject(Provider::APP_TOKEN_CREATED, ['name' => $event->getToken()->getName()])
			->setObject('app_token', $event->getToken()->getId());

		try {
			$this->activityManager->publish($activity);
		} catch (BadMethodCallException $e) {
			$this->logger->warning('Could not publish activity: ' . $e->getMessage(), [
				'exception' => $e
			]);
		}
	}
}
