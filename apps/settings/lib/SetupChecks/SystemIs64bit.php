<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class SystemIs64bit implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Architecture');
	}

	public function getCategory(): string {
		return 'system';
	}

	protected function is64bit(): bool {
		if (PHP_INT_SIZE < 8) {
			return false;
		} else {
			return true;
		}
	}

	public function run(): SetupResult {
		if ($this->is64bit()) {
			return SetupResult::success($this->l10n->t('64-bit'));
		} else {
			return SetupResult::warning(
				$this->l10n->t('It seems like you are running a 32-bit PHP version. Nextcloud needs 64-bit to run well. Please upgrade your OS and PHP to 64-bit!'),
				$this->urlGenerator->linkToDocs('admin-system-requirements')
			);
		}
	}
}
