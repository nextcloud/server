<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class NeedsSystemAddressBookSync implements ISetupCheck {
	public function __construct(
		private IConfig $config,
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('DAV system address book');
	}

	public function getCategory(): string {
		return 'dav';
	}

	public function run(): SetupResult {
		if ($this->config->getAppValue('dav', 'needs_system_address_book_sync', 'no') === 'no') {
			return SetupResult::success($this->l10n->t('No outstanding DAV system address book sync.'));
		} else {
			return SetupResult::warning($this->l10n->t('The DAV system address book sync has not run yet as your instance has more than 1000 users or because an error occurred. Please run it manually by calling "occ dav:sync-system-addressbook".'));
		}
	}
}
