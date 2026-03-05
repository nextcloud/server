<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\SetupChecks;

use OCA\DAV\AppInfo\Application;
use OCA\DAV\ConfigLexicon;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class SystemAddressBookSize implements ISetupCheck {
	public function __construct(
		private IAppConfig $appConfig,
		private IUserManager $userManager,
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('DAV system address book size');
	}

	public function getCategory(): string {
		return 'dav';
	}

	public function run(): SetupResult {
		if (!$this->appConfig->getValueBool(Application::APP_ID, ConfigLexicon::SYSTEM_ADDRESSBOOK_EXPOSED)) {
			return SetupResult::success($this->l10n->t('The system address book is disabled'));
		}

		// We use count seen because getting a user count from the backend can be very slow
		$count = $this->userManager->countSeenUsers();
		$limit = $this->appConfig->getValueInt(Application::APP_ID, 'system_addressbook_limit', 5000);

		if ($count > $limit) {
			return SetupResult::warning($this->l10n->t('The system address book is enabled, but contains more than the configured limit of %d contacts', [$limit]));
		} else {
			return SetupResult::success($this->l10n->t('The system address book is enabled and contains less than the configured limit of %d contacts', [$limit]));
		}
	}
}
