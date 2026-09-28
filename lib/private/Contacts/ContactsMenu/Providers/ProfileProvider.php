<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Contacts\ContactsMenu\Providers;

use OC\Profile\ProfileManager;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory as IL10NFactory;

class ProfileProvider implements IProvider {
	public function __construct(
		private IActionFactory $actionFactory,
		private ProfileManager $profileManager,
		private IL10NFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
		private IUserManager $userManager,
	) {
	}

	public function process(IEntry $entry): void {
		$targetUserId = $entry->getProperty('UID');
		$targetUser = $this->userManager->get($targetUserId);
		if (!empty($targetUser)) {
			if ($this->profileManager->isProfileEnabled($targetUser)) {
				$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/profile.svg'));
				$profileActionText = $this->l10nFactory->get('lib')->t('View profile');
				$profileUrl = $this->urlGenerator->linkToRouteAbsolute('profile.ProfilePage.index', ['targetUserId' => $targetUserId]);
				$action = $this->actionFactory->newLinkAction($iconUrl, $profileActionText, $profileUrl, 'profile');
				// Set highest priority (by descending order), other actions have the default priority 10 as defined in lib/private/Contacts/ContactsMenu/Actions/LinkAction.php
				$action->setPriority(20);
				$entry->addAction($action);
			}
		}
	}
}
