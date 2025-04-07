<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesReminders\SetupChecks;

use OCP\App\IAppManager;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class NeedNotificationsApp implements ISetupCheck {
	public function __construct(
		private IAppManager $appManager,
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Files reminder');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function run(): SetupResult {
		if ($this->appManager->isEnabledForAnyone('notifications')) {
			return SetupResult::success($this->l10n->t('This files_reminder can work properly.'));
		} else {
			return SetupResult::warning($this->l10n->t('The files_reminder app needs the notification app to work properly. You should either enable notifications or disable files_reminder.'));
		}
	}
}
