<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Authentication\Listeners;

use OC\Authentication\Events\RemoteWipeFinished;
use OC\Authentication\Events\RemoteWipeStarted;
use OC\Authentication\Token\IToken;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Notification\IManager as INotificationManager;

/**
 * @template-implements IEventListener<\OC\Authentication\Events\ARemoteWipeEvent>
 */
class RemoteWipeNotificationsListener implements IEventListener {
	/** @var INotificationManager */
	private $notificationManager;

	/** @var ITimeFactory */
	private $timeFactory;

	public function __construct(INotificationManager $notificationManager,
		ITimeFactory $timeFactory) {
		$this->notificationManager = $notificationManager;
		$this->timeFactory = $timeFactory;
	}

	public function handle(Event $event): void {
		if ($event instanceof RemoteWipeStarted) {
			$this->sendNotification('remote_wipe_start', $event->getToken());
		} elseif ($event instanceof RemoteWipeFinished) {
			$this->sendNotification('remote_wipe_finish', $event->getToken());
		}
	}

	private function sendNotification(string $event, IToken $token): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp('auth')
			->setUser($token->getUID())
			->setDateTime($this->timeFactory->getDateTime())
			->setObject('token', (string)$token->getId())
			->setSubject($event, [
				'name' => $token->getName(),
			]);
		$this->notificationManager->notify($notification);
	}
}
