<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class OverwriteCliUrl implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IRequest $request,
	) {
	}

	public function getCategory(): string {
		return 'config';
	}

	public function getName(): string {
		return $this->l10n->t('Overwrite CLI URL');
	}

	public function run(): SetupResult {
		$currentOverwriteCliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
		$suggestedOverwriteCliUrl = $this->request->getServerProtocol() . '://' . $this->request->getInsecureServerHost() . \OC::$WEBROOT;

		// Check correctness by checking if it is a valid URL
		if (filter_var($currentOverwriteCliUrl, FILTER_VALIDATE_URL)) {
			if ($currentOverwriteCliUrl == $suggestedOverwriteCliUrl) {
				return SetupResult::success(
					$this->l10n->t(
						'The "overwrite.cli.url" option in your config.php is correctly set to "%s".',
						[$currentOverwriteCliUrl]
					)
				);
			} else {
				return SetupResult::success(
					$this->l10n->t(
						'The "overwrite.cli.url" option in your config.php is set to "%s" which is a correct URL. Suggested URL is "%s".',
						[$currentOverwriteCliUrl, $suggestedOverwriteCliUrl]
					)
				);
			}
		} else {
			return SetupResult::warning(
				$this->l10n->t(
					'Please make sure to set the "overwrite.cli.url" option in your config.php file to the URL that your users mainly use to access this Nextcloud. Suggestion: "%s". Otherwise there might be problems with the URL generation via cron. (It is possible though that the suggested URL is not the URL that your users mainly use to access this Nextcloud. Best is to double check this in any case.)',
					[$suggestedOverwriteCliUrl]
				)
			);
		}
	}
}
