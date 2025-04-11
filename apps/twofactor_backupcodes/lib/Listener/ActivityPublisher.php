<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Listener;

use BadMethodCallException;
use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCP\Activity\IManager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<CodesGenerated> */
class ActivityPublisher implements IEventListener {
	public function __construct(
		private IManager $activityManager,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Push an event to the user's activity stream
	 */
	public function handle(Event $event): void {
		if ($event instanceof CodesGenerated) {
			$activity = $this->activityManager->generateEvent();
			$activity->setApp('twofactor_backupcodes')
				->setType('security')
				->setAuthor($event->getUser()->getUID())
				->setAffectedUser($event->getUser()->getUID())
				->setSubject('codes_generated');
			try {
				$this->activityManager->publish($activity);
			} catch (BadMethodCallException $e) {
				$this->logger->error('Could not publish backup code creation activity', ['exception' => $e]);
			}
		}
	}
}
