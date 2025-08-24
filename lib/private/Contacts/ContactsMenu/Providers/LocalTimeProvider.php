<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Contacts\ContactsMenu\Providers;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IConfig;
use OCP\IDateTimeFormatter;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;

class LocalTimeProvider implements IProvider {
	public function __construct(
		private IActionFactory $actionFactory,
		private IL10NFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
		private ITimeFactory $timeFactory,
		private IDateTimeFormatter $dateTimeFormatter,
		private IConfig $config,
		private IUserSession $currentSession,
	) {
	}

	public function process(IEntry $entry): void {
		$targetUserId = $entry->getProperty('UID');
		$targetUser = $this->userManager->get($targetUserId);
		if (!empty($targetUser)) {
			$timezoneStringTarget = $this->config->getUserValue($targetUser->getUID(), 'core', 'timezone') ?: $this->config->getSystemValueString('default_timezone', 'UTC');
			$timezoneTarget = new \DateTimeZone($timezoneStringTarget);
			$localTimeTarget = $this->timeFactory->getDateTime('now', $timezoneTarget);
			$localTimeString = $this->dateTimeFormatter->formatTime($localTimeTarget, 'short', $timezoneTarget);

			$l = $this->l10nFactory->get('lib');
			$currentUser = $this->currentSession->getUser();
			if ($currentUser !== null) {
				$timezoneStringCurrent = $this->config->getUserValue($currentUser->getUID(), 'core', 'timezone') ?: $this->config->getSystemValueString('default_timezone', 'UTC');
				$timezoneCurrent = new \DateTimeZone($timezoneStringCurrent);
				$localTimeCurrent = $this->timeFactory->getDateTime('now', $timezoneCurrent);

				// Get the timezone offsets to GMT on this very time (needed to handle daylight saving time)
				$timeOffsetCurrent = $timezoneCurrent->getOffset($localTimeCurrent);
				$timeOffsetTarget = $timezoneTarget->getOffset($localTimeTarget);
				// Get the difference between the current users offset to GMT and then targets user to GMT
				$timeOffset = $timeOffsetTarget - $timeOffsetCurrent;
				if ($timeOffset === 0) {
					// No offset means both users are in the same timezone
					$timeOffsetString = $l->t('same time');
				} else {
					// We need to cheat here as the offset could be up to 26h we can not use formatTime.
					$hours = abs((int)($timeOffset / 3600));
					$minutes = abs(($timeOffset / 60) % 60);
					// TRANSLATORS %n hours in a short form
					$hoursString = $l->n('%nh', '%nh', $hours);
					// TRANSLATORS %n minutes in a short form
					$minutesString = $l->n('%nm', '%nm', $minutes);

					$timeOffsetString = ($hours > 0 ? $hoursString : '') . ($minutes > 0 ? $minutesString : '');

					if ($timeOffset > 0) {
						// TRANSLATORS meaning the user is %s time ahead - like 1h30m
						$timeOffsetString = $l->t('%s ahead', [$timeOffsetString]);
					} else {
						// TRANSLATORS meaning the user is %s time behind - like 1h30m
						$timeOffsetString = $l->t('%s behind', [$timeOffsetString]);
					}
				}
				$profileActionText = "{$localTimeString} • {$timeOffsetString}";
			} else {
				$profileActionText = $l->t('Local time: %s', [$localTimeString]);
			}

			$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/recent.svg'));
			$action = $this->actionFactory->newLinkAction($iconUrl, $profileActionText, '#', 'timezone');
			// Order after the profile page
			$action->setPriority(19);
			$entry->addAction($action);
		}
	}
}
