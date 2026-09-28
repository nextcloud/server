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

class PhpFreetypeSupport implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Freetype');
	}

	public function getCategory(): string {
		return 'php';
	}

	/**
	 * Check if the required FreeType functions are present
	 */
	protected function hasFreeTypeSupport(): bool {
		return function_exists('imagettfbbox') && function_exists('imagettftext');
	}

	public function run(): SetupResult {
		if ($this->hasFreeTypeSupport()) {
			return SetupResult::success($this->l10n->t('Supported'));
		} else {
			return SetupResult::info(
				$this->l10n->t('Your PHP does not have FreeType support, resulting in breakage of profile pictures and the settings interface.'),
			);
		}
	}
}
