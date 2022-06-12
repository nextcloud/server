<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	private IActionFactory $actionFactory;
	private ProfileManager $profileManager;
	private IL10NFactory $l10nFactory;
	private IURLGenerator $urlGenerator;
	private IUserManager $userManager;

	public function __construct(
		IActionFactory $actionFactory,
		ProfileManager $profileManager,
		IL10NFactory $l10nFactory,
		IURLGenerator $urlGenerator,
		IUserManager $userManager
	) {
		$this->actionFactory = $actionFactory;
		$this->profileManager = $profileManager;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
	}

	/**
	 * @param IEntry $entry
	 */
	public function process(IEntry $entry) {
		$targetUserId = $entry->getProperty('UID');
		$targetUser = $this->userManager->get($targetUserId);
		if (!empty($targetUser)) {
			if ($this->profileManager->isProfileEnabled($targetUser)) {
				$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/profile.svg'));
				$profileActionText = $this->l10nFactory->get('lib')->t('View profile');
				$profileUrl = $this->urlGenerator->linkToRouteAbsolute('core.ProfilePage.index', ['targetUserId' => $targetUserId]);
				$action = $this->actionFactory->newLinkAction($iconUrl, $profileActionText, $profileUrl, 'profile');
				// Set highest priority (by descending order), other actions have the default priority 10 as defined in lib/private/Contacts/ContactsMenu/Actions/LinkAction.php
				$action->setPriority(20);
				$entry->addAction($action);
			}
		}
	}
}
