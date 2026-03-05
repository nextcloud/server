<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Security\ISecureRandom;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class RandomnessSecure implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private ISecureRandom $secureRandom,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Random generator');
	}

	public function getCategory(): string {
		return 'security';
	}

	public function run(): SetupResult {
		try {
			$this->secureRandom->generate(1);
		} catch (\Exception $ex) {
			return SetupResult::error(
				$this->l10n->t('No suitable source for randomness found by PHP which is highly discouraged for security reasons.'),
				$this->urlGenerator->linkToDocs('admin-security')
			);
		}
		return SetupResult::success($this->l10n->t('Secure'));
	}
}
