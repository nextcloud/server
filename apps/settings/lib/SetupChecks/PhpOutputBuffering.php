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

class PhpOutputBuffering implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getCategory(): string {
		return 'php';
	}

	public function getName(): string {
		return $this->l10n->t('PHP "output_buffering" option');
	}

	public function run(): SetupResult {
		$value = trim(ini_get('output_buffering'));
		if ($value === '' || $value === '0') {
			return SetupResult::success($this->l10n->t('Disabled'));
		} else {
			return SetupResult::error($this->l10n->t('PHP configuration option "output_buffering" must be disabled'));
		}
	}
}
