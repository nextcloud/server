<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpDisabledFunctions implements ISetupCheck {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP set_time_limit');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		if (function_exists('set_time_limit') && !str_contains(ini_get('disable_functions'), 'set_time_limit')) {
			return SetupResult::success($this->l10n->t('The function is available.'));
		} else {
			return SetupResult::warning(
				$this->l10n->t('The PHP function "set_time_limit" is not available. This could result in scripts being halted mid-execution, breaking your installation. Enabling this function is strongly recommended.'),
			);
		}
	}
}
