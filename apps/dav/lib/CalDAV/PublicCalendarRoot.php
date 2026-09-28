<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CalDAV;

use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\Uri;

class PublicCalendarRoot extends Collection {

	/**
	 * PublicCalendarRoot constructor.
	 *
	 * @param CalDavBackend $caldavBackend
	 * @param IL10N $l10n
	 * @param IConfig $config
	 * @param IAppConfig $appConfig
	 */
	public function __construct(
		protected CalDavBackend $caldavBackend,
		protected IL10N $l10n,
		protected IConfig $config,
		protected IAppConfig $appConfig,
		private LoggerInterface $logger,
		private IUserManager $userManager,
	) {
	}

	/**
	 * @inheritdoc
	 */
	public function getName() {
		return 'public-calendars';
	}

	/**
	 * @inheritdoc
	 */
	public function getChild($name) {
		$calendar = $this->caldavBackend->getPublicCalendar($name);
		if (!$this->validateVisibility((string)$calendar['principaluri'])) {
			throw new NotFound('Node with name \'' . $name . '\' could not be found');
		}
		return new PublicCalendar($this->caldavBackend, $calendar, $this->l10n, $this->config, $this->logger);
	}

	/**
	 * @inheritdoc
	 */
	public function getChildren() {
		return [];
	}

	/**
	 * Checks if the public calendar should be visible or not, based on
	 * the configuration of the `hide_disabled_user_shares` setting within
	 * `files_sharing` and the status of the owning user (disabled or not).
	 */
	private function validateVisibility(string $principalUri): bool {
		$hideCalendarsOfDisabledUsers = $this->appConfig->getValueBool(
			'files_sharing', 'hide_disabled_user_shares', true
		);

		if (!$hideCalendarsOfDisabledUsers) {
			return true;
		}

		[$prefix, $name] = Uri\split($principalUri);
		if ($prefix !== 'principals/users') {
			return true;
		}

		return $this->userManager->get((string)$name)?->isEnabled() !== false;
	}
}
