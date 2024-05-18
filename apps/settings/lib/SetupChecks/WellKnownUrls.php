<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

class WellKnownUrls implements ISetupCheck {

	use CheckServerResponseTrait;

	public function __construct(
		protected IL10N $l10n,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IClientService $clientService,
		protected LoggerInterface $logger,
	) {
	}

	public function getCategory(): string {
		return 'network';
	}

	public function getName(): string {
		return $this->l10n->t('.well-known URLs');
	}

	public function run(): SetupResult {
		if (!$this->config->getSystemValueBool('check_for_working_wellknown_setup', true)) {
			return SetupResult::info($this->l10n->t('`check_for_working_wellknown_setup` is set to false in your configuration, so this check was skipped.'));
		}

		$urls = [
			['get', '/.well-known/webfinger', [200, 404], true],
			['get', '/.well-known/nodeinfo', [200, 404], true],
			['propfind', '/.well-known/caldav', [207], false],
			['propfind', '/.well-known/carddav', [207], false],
		];

		foreach ($urls as [$verb,$url,$validStatuses,$checkCustomHeader]) {
			$works = null;
			foreach ($this->runRequest($verb, $url, ['httpErrors' => false, 'options' => ['allow_redirects' => ['track_redirects' => true]]]) as $response) {
				// Check that the response status matches
				$works = in_array($response->getStatusCode(), $validStatuses);
				// and (if needed) the custom Nextcloud header is set
				if ($checkCustomHeader) {
					$works = $works && !empty($response->getHeader('X-NEXTCLOUD-WELL-KNOWN'));
				} else {
					// For default DAV endpoints we lack authorization, but we still can check that the redirect works as expected
					if (!$works && $response->getStatusCode() === 401) {
						$redirectHops = explode(',', $response->getHeader('X-Guzzle-Redirect-History'));
						$effectiveUri = end($redirectHops);
						$works = str_ends_with($effectiveUri, '/remote.php/dav/');
					}
				}
				// Skip the other requests if one works
				if ($works === true) {
					break;
				}
			}
			// If 'works' is null then we could not connect to the server
			if ($works === null) {
				return SetupResult::info(
					$this->l10n->t('Could not check that your web server serves `.well-known` correctly. Please check manually.') . "\n" . $this->serverConfigHelp(),
					$this->urlGenerator->linkToDocs('admin-setup-well-known-URL'),
				);
			}
			// Otherwise if we fail we can abort here
			if ($works === false) {
				return SetupResult::warning(
					$this->l10n->t("Your web server is not properly set up to resolve `.well-known` URLs, failed on:\n`%s`", [$url]),
					$this->urlGenerator->linkToDocs('admin-setup-well-known-URL'),
				);
			}
		}
		return SetupResult::success(
			$this->l10n->t('Your server is correctly configured to serve `.well-known` URLs.')
		);
	}
}
