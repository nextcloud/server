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
	) {
	}

	public function process(IEntry $entry): void {
		$targetUserId = $entry->getProperty('UID');
		$targetUser = $this->userManager->get($targetUserId);
		if (!empty($targetUser)) {
			$timezone = $this->config->getUserValue($targetUser->getUID(), 'core', 'timezone') ?: date_default_timezone_get();
			$dateTimeZone = new \DateTimeZone($timezone);
			$localTime = $this->dateTimeFormatter->formatTime($this->timeFactory->getDateTime(), 'short', $dateTimeZone);

			$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/recent.svg'));
			$l = $this->l10nFactory->get('lib');
			$profileActionText = $l->t('Local time: %s', [$localTime]);

			$action = $this->actionFactory->newLinkAction($iconUrl, $profileActionText, '#', 'timezone');
			// Order after the profile page
			$action->setPriority(19);
			$entry->addAction($action);
		}
	}
}
