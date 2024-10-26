<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\BackgroundJob\RefreshWebcalJob;
use OCA\DAV\CalDAV\Reminder\Backend as ReminderBackend;
use OCA\DAV\CalDAV\WebcalCaching\RefreshWebcalService;
use OCA\DAV\Events\SubscriptionCreatedEvent;
use OCA\DAV\Events\SubscriptionDeletedEvent;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<SubscriptionCreatedEvent|SubscriptionDeletedEvent> */
class SubscriptionListener implements IEventListener {
	public function __construct(
		private IJobList $jobList,
		private RefreshWebcalService $refreshWebcalService,
		private ReminderBackend $reminderBackend,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * In case the user has set their default calendar to the deleted one
	 */
	public function handle(Event $event): void {
		if ($event instanceof SubscriptionCreatedEvent) {
			$subscriptionId = $event->getSubscriptionId();
			$subscriptionData = $event->getSubscriptionData();

			$this->logger->debug('Refreshing webcal data for subscription ' . $subscriptionId);
			$this->refreshWebcalService->refreshSubscription(
				(string)$subscriptionData['principaluri'],
				(string)$subscriptionData['uri']
			);

			$this->logger->debug('Scheduling webcal data refreshment for subscription ' . $subscriptionId);
			$this->jobList->add(RefreshWebcalJob::class, [
				'principaluri' => $subscriptionData['principaluri'],
				'uri' => $subscriptionData['uri']
			]);
		} elseif ($event instanceof SubscriptionDeletedEvent) {
			$subscriptionId = $event->getSubscriptionId();
			$subscriptionData = $event->getSubscriptionData();

			$this->logger->debug('Removing refresh webcal job for subscription ' . $subscriptionId);
			$this->jobList->remove(RefreshWebcalJob::class, [
				'principaluri' => $subscriptionData['principaluri'],
				'uri' => $subscriptionData['uri']
			]);

			$this->logger->debug('Cleaning all reminders for subscription ' . $subscriptionId);
			$this->reminderBackend->cleanRemindersForCalendar($subscriptionId);
		}
	}
}
