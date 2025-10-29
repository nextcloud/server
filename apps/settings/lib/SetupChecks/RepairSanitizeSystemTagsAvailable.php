<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Repair\RepairSanitizeSystemTags;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class RepairSanitizeSystemTagsAvailable implements ISetupCheck {

	public function __construct(
		private RepairSanitizeSystemTags $repairSanitizeSystemTags,
		private IL10N $l10n,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Sanitize and merge duplicate system tags available');
	}

	public function run(): SetupResult {
		if ($this->repairSanitizeSystemTags->migrationsAvailable()) {
			return SetupResult::warning(
				$this->l10n->t('One or more system tags need to be sanitized or merged. This can take a long time on larger instances so this is not done automatically during upgrades. Use the command `occ maintenance:repair --include-expensive` to perform the migrations.'),
			);
		} else {
			return SetupResult::success('None');
		}
	}
}
