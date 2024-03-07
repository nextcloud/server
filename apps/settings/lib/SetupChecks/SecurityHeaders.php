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

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

class SecurityHeaders implements ISetupCheck {

	use CheckServerResponseTrait;

	public function __construct(
		protected IL10N $l10n,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IRequest $request,
		protected IClientService $clientService,
		protected LoggerInterface $logger,
	) {
	}

	public function getCategory(): string {
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('HTTP headers');
	}

	public function run(): SetupResult {
		$urls = [
			['get', $this->urlGenerator->linkToRoute('heartbeat'), [200]],
		];
		$securityHeaders = [
			'X-Content-Type-Options' => ['nosniff', null],
			'X-Robots-Tag' => ['noindex, nofollow', null],
			'X-Frame-Options' => ['sameorigin', 'deny'],
			'X-Permitted-Cross-Domain-Policies' => ['none', null],
		];

		foreach ($urls as [$verb,$url,$validStatuses]) {
			$works = null;
			foreach ($this->runRequest($url, $verb) as $response) {
				// Check that the response status matches
				if (!in_array($response->getStatusCode(), $validStatuses)) {
					$works = false;
					continue;
				}
				$msg = '';
				$msgParameters = [];
				foreach ($securityHeaders as $header => [$expected, $accepted]) {
					$value = strtolower($response->getHeader($header));
					if ($value !== $expected) {
						if ($accepted !== null && $value === $accepted) {
							$msg .= $this->l10n->t('- The `%1` HTTP header is not set to `%2`. Some features might not work correctly, as it is recommended to adjust this setting accordingly.', [$header, $expected]);
						} else {
							$msg .= $this->l10n->t('- The `%1` HTTP header is not set to `%2`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.', [$header, $expected]);
						}
					}
				}

				$xssfields = array_map('trim', explode(';', $response->getHeader('X-XSS-Protection')));
				if (!in_array('1', $xssfields) || !in_array('mode=block', $xssfields)) {
					$msg .= $this->l10n->t('- The `%1` HTTP header does not contain `%2`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.', ['X-XSS-Protection', '1; mode=block']);
				}

				$referrerPolicy = $response->getHeader('Referrer-Policy');
				if ($referrerPolicy === null || !preg_match('/(no-referrer(-when-downgrade)?|strict-origin(-when-cross-origin)?|same-origin)(,|$)/', $referrerPolicy)) {
					$msg .= $this->l10n->t(
						'- The `%1` HTTP header is not set to `%2`, `%3`, `%4`, `%5` or `%6`. This can leak referer information. See the {w3c-recommendation}.',
						[
							'Referrer-Policy',
							'no-referrer',
							'no-referrer-when-downgrade',
							'strict-origin',
							'strict-origin-when-cross-origin',
							'same-origin',
						]
					);
					$msgParameters['w3c-recommendation'] = [
						'type' => 'highlight',
						'id' => 'w3c-recommendation',
						'name' => 'W3C Recommendation',
						'link' => 'https://www.w3.org/TR/referrer-policy/',
					];
				}
				if (!empty($msg)) {
					return SetupResult::warning($this->l10n->t('Some headers are not set correctly on your instance')."\n".$msg, descriptionParameters:$msgParameters);
				}
				// Skip the other requests if one works
				break;
			}
			// If 'works' is null then we could not connect to the server
			if ($works === null) {
				return SetupResult::info(
					$this->l10n->t('Could not check that your web server serves security headers correctly. Please check manually.'),
				);
			}
		}
		return SetupResult::success(
			$this->l10n->t('Your server is correctly configured to send security headers.')
		);
	}
}
