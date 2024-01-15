<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
		return $this->l10n->t('Overwrite cli URL');
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
