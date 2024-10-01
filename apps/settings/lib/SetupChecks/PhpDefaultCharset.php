<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpDefaultCharset implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP default charset');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		if (strtoupper(trim(ini_get('default_charset'))) === 'UTF-8') {
			return SetupResult::success('UTF-8');
		} else {
			return SetupResult::warning($this->l10n->t('PHP configuration option "default_charset" should be UTF-8'));
		}
	}
}
