<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\ContactsInteraction\Listeners;

use OCA\ContactsInteraction\AppInfo\Application;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\BackgroundJob\IJobList;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<BeforePreferenceSetEvent|BeforePreferenceDeletedEvent> */
class UserPreferenceListener implements IEventListener {

	public function __construct(protected IJobList $jobList, private RecentContactMapper $recentContactMapper) { }

	public function handle(Event $event): void {
		if (!$event instanceof BeforePreferenceSetEvent && !$event instanceof BeforePreferenceDeletedEvent) {
			return;
		}

		if ($event->getAppId() !== Application::APP_ID || $event->getConfigKey() !== 'disableContactsInteractionAddressBook') {
			return;
		}

		$enabled = $event->getConfigValue() === 'yes';
		$event->setValid($enabled);
		if (!$enabled) {
			$this->recentContactMapper->cleanForUser($event->getUserId());
		}
	}
}
