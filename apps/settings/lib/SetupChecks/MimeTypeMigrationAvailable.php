<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OC\Repair\RepairMimeTypes;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MimeTypeMigrationAvailable implements ISetupCheck {
	private IL10N $l10n;

	public function __construct(
		IFactory $l10nFactory,
		private RepairMimeTypes $repairMimeTypes,
	) {
		$this->l10n = $l10nFactory->get('core');
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('Mimetype migrations available');
	}

	public function run(): SetupResult {
		if ($this->repairMimeTypes->migrationsAvailable()) {
			return SetupResult::warning(
				$this->l10n->t('One or more mimetype migrations are available. Occasionally new mimetypes are added to better handle certain file types. Migrating the mimetypes take a long time on larger instances so this is not done automatically during upgrades. Use the command `occ maintenance:repair --include-expensive` to perform the migrations.'),
			);
		} else {
			return SetupResult::success('None');
		}
	}
}
