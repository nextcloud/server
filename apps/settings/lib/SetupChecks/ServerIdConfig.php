<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Override;

final class ServerIdConfig implements ISetupCheck {
	public function __construct(
		private readonly IL10N $l10n,
		private readonly IConfig $config,
		private readonly IURLGenerator $urlGenerator,
	) {
	}

	#[Override]
	public function getName(): string {
		return $this->l10n->t('Configuration server ID');
	}

	#[Override]
	public function getCategory(): string {
		return 'config';
	}

	#[Override]
	public function run(): SetupResult {
		$serverid = $this->config->getSystemValueInt('serverid', PHP_INT_MIN);
		$linkToDoc = $this->urlGenerator->linkToDocs('admin-update');

		if ($serverid === PHP_INT_MIN) {
			return SetupResult::info(
				$this->l10n->t('Server identifier isn’t configured. It is recommended if your Nextcloud instance is running on several PHP servers. Add a server ID in your configuration.'),
				$linkToDoc,
			);
		}

		if ($serverid < 0 || $serverid > 1023) {
			return SetupResult::error(
				$this->l10n->t('"%d" is not a valid server identifier. It must be between 0 and 1023.', [$serverid]),
				$linkToDoc,
			);
		}

		return SetupResult::success($this->l10n->t('Server identifier is configured and valid.'));
	}
}
