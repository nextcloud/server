<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
	private IActionFactory $actionFactory;
	private IL10NFactory $l10nFactory;
	private IURLGenerator $urlGenerator;
	private IUserManager $userManager;
	private ITimeFactory $timeFactory;
	private IDateTimeFormatter $dateTimeFormatter;
	private IConfig $config;

	public function __construct(
		IActionFactory $actionFactory,
		IL10NFactory $l10nFactory,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		ITimeFactory $timeFactory,
		IDateTimeFormatter $dateTimeFormatter,
		IConfig $config
	) {
		$this->actionFactory = $actionFactory;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->timeFactory = $timeFactory;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->config = $config;
	}

	/**
	 * @param IEntry $entry
	 */
	public function process(IEntry $entry) {
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
