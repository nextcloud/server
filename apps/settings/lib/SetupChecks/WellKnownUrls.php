<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
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

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class WellKnownUrls implements ISetupCheck {
	public function __construct(
		private IConfig $config,
		private IL10N $l10n,
		private IURLGenerator $urlGenerator,
		private IRequest $request,
		private IClientService $httpClientService,
	) {
	}

	public function getCategory(): string {
		return 'system';
	}

	public function getName(): string {
		return $this->l10n->t('.well-known URLs');
	}

	/**
	 * @param 'get'|'propfind' $verb
	 */
	private function checkGetUrl(string $verb, string $url, array $validStatuses, bool $checkCustomHeader): bool {
		$client = $this->httpClientService->newClient();
		$response = $client->$verb($this->urlGenerator->getAbsoluteURL($url), ['verify' => false, 'http_errors' => false]);
		if (!in_array($response->getStatusCode(), $validStatuses)) {
			return false;
		}
		if ($checkCustomHeader && empty($response->getHeader('X-NEXTCLOUD-WELL-KNOWN'))) {
			return false;
		}
		return true;
	}

	public function run(): SetupResult {
		if (!$this->config->getSystemValueBool('check_for_working_wellknown_setup', true)) {
			return SetupResult::success($this->l10n->t('`check_for_working_wellknown_setup` is set to false in your configuration, so this check was skipped.'));
		}
		try {
			$checkList = '';
			$level = 'success';
			$urls = [
				['get', '/.well-known/webfinger', [200, 404], true],
				['get', '/.well-known/nodeinfo', [200, 404], true],
				['propfind', '/.well-known/caldav', [207], false],
				['propfind', '/.well-known/carddav', [207], false],
			];
			foreach ($urls as [$verb,$url,$validStatuses,$checkCustomHeader]) {
				if (!$this->checkGetUrl($verb, $url, $validStatuses, $checkCustomHeader)) {
					$level = 'info';
					$checkList .= ' - '.strtoupper($verb).' '.$url.': failure'."\n";
				} else {
					$checkList .= ' - '.strtoupper($verb).' '.$url.': success'."\n";
				}
			}
			return match($level) {
				'success' => SetupResult::success(
					$this->l10n->t("Your web server is correctly configured to serve `.well-known` URLs:\n%s", [$checkList]),
					$this->urlGenerator->linkToDocs('admin-setup-well-known-URL')
				),
				'info' => SetupResult::info(
					$this->l10n->t("Your web server is not properly set up to resolve well-known URLs:\n%s", [$checkList]),
					$this->urlGenerator->linkToDocs('admin-setup-well-known-URL')
				),
			};
		} catch (\Exception $e) {
			return SetupResult::error(
				$this->l10n->t('Failed to test .well-known URLs: "%s".', [$e->getMessage()]),
			);
		}
		/*
		 * TODO:
			// OC.SetupChecks.checkWellKnownUrl('PROPFIND', '/.well-known/caldav', OC.theme.docPlaceholderUrl),
			// OC.SetupChecks.checkWellKnownUrl('PROPFIND', '/.well-known/carddav', OC.theme.docPlaceholderUrl),
			OC.SetupChecks.checkProviderUrl(OC.getRootPath() + '/ocm-provider/', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			OC.SetupChecks.checkProviderUrl(OC.getRootPath() + '/ocs-provider/', OC.theme.docPlaceholderUrl, $('#postsetupchecks').data('check-wellknown') === true),
			Valid status is 207
		*/
		return SetupResult::success(
			$this->l10n->t('Your server is correctly configured to serve `.well-known` URLs.')
		);
	}
}
