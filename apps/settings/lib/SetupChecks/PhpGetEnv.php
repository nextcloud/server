<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class PhpGetEnv implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('PHP getenv');
	}

	public function getCategory(): string {
		return 'php';
	}

	public function run(): SetupResult {
		if (!empty(getenv('PATH'))) {
			return SetupResult::success();
		} else {
			return SetupResult::warning($this->l10n->t('PHP does not seem to be setup properly to query system environment variables. The test with getenv("PATH") only returns an empty response.'), $this->urlGenerator->linkToDocs('admin-php-fpm'));
		}
	}
}
