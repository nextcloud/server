<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class LegacySSEKeyFormat implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('Old server-side-encryption');
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValueBool('encryption.legacy_format_support', false) === false) {
			return SetupResult::success($this->l10n->t('Disabled'));
		}
		return SetupResult::warning($this->l10n->t('The old server-side-encryption format is enabled. We recommend disabling this.'), $this->urlGenerator->linkToDocs('admin-sse-legacy-format'));
	}
}
