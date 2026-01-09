<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\SetupChecks;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\CheckServerResponseTrait;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Class SecurityHeaders
 *
 * Performs setup checks to verify that essential HTTP security headers are correctly configured
 * on the Nextcloud instance. The check issues warnings or informational messages if recommended
 * security headers are missing, malformed, or set to unsafe values.
 *
 * This class is used by the Nextcloud setup process to ensure that the web server delivers
 * responses with proper security headers, helping to protect against common web vulnerabilities.
 */
class SecurityHeaders implements ISetupCheck {

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
		return 'security';
	}

	public function getName(): string {
		return $this->l10n->t('HTTP headers');
	}

	/**
	 * Executes the security header setup check.
	 *
	 * This method sends HTTP requests to the server and analyzes the response headers
	 * to verify that essential security-related HTTP headers (such as X-Content-Type-Options,
	 * X-Robots-Tag, X-Frame-Options, X-Permitted-Cross-Domain-Policies, Referrer-Policy,
	 * and Strict-Transport-Security) are set correctly and meet recommended values.
	 *
	 * Returns a SetupResult indicating whether the server is properly configured,
	 * provides warnings for misconfiguration, or informational messages if the check
	 * cannot be performed.
	 *
	 * @return SetupResult Result of the security headers setup check.
	 */
	public function run(): SetupResult {
		$urls = [
			['get', $this->urlGenerator->linkToRoute('heartbeat'), [200]],
		];
		$securityHeaders = [
			'X-Content-Type-Options' => ['nosniff', null],
			'X-Robots-Tag' => ['noindex,nofollow', null],
			'X-Frame-Options' => ['sameorigin', 'deny'],
			'X-Permitted-Cross-Domain-Policies' => ['none', null],
		];

		foreach ($urls as [$verb, $url, $validStatuses]) {
			$works = null;
			foreach ($this->runRequest($verb, $url, ['httpErrors' => false]) as $response) {
				// Check that the response status matches
				if (!in_array($response->getStatusCode(), $validStatuses)) {
					$works = false;
					continue;
				}
				$msg = '';
				$msgParameters = [];
				foreach ($securityHeaders as $header => [$expected, $accepted]) {
					/* Convert to lowercase and remove spaces after commas */
					$value = preg_replace('/,\s+/', ',', strtolower($response->getHeader($header)));
					if ($value !== $expected) {
						if ($accepted !== null && $value === $accepted) {
							$msg .= $this->l10n->t(
								'- The `%1$s` HTTP header is not set to `%2$s`. Some features '
								. 'might not work correctly, as it is recommended to adjust this '
								. 'setting accordingly.',
								[$header, $expected]
							) . "\n";
						} else {
							$msg .= $this->l10n->t(
								'- The `%1$s` HTTP header is not set to `%2$s`. This is a '
								. 'potential security or privacy risk, as it is recommended to adjust '
								. 'this setting accordingly.',
								[$header, $expected]
							) . "\n";
						}
					}
				}

				$referrerPolicy = $response->getHeader('Referrer-Policy');
				if (!preg_match('/(no-referrer(-when-downgrade)?|strict-origin(-when-cross-origin)?|same-origin)(,|$)/', $referrerPolicy)) {
					$msg .= $this->l10n->t(
						'- The `%1$s` HTTP header is not set to `%2$s`, `%3$s`, `%4$s`, `%5$s` or `%6$s`. '
						. 'This can leak referer information. See the {w3c-recommendation}.',
						[
							'Referrer-Policy',
							'no-referrer',
							'no-referrer-when-downgrade',
							'strict-origin',
							'strict-origin-when-cross-origin',
							'same-origin',
						]
					) . "\n";
					$msgParameters['w3c-recommendation'] = [
						'type' => 'highlight',
						'id' => 'w3c-recommendation',
						'name' => 'W3C Recommendation',
						'link' => 'https://www.w3.org/TR/referrer-policy/',
					];
				}

				$transportSecurityValidity = $response->getHeader('Strict-Transport-Security');
				$minimumSeconds = 15552000;
				if (preg_match('/^max-age=(\d+)(;.*)?$/', $transportSecurityValidity, $m)) {
					$transportSecurityValidity = (int)$m[1];
					if ($transportSecurityValidity < $minimumSeconds) {
						$msg .= $this->l10n->t(
    						'- The `Strict-Transport-Security` HTTP header is set below the recommended minimum of `%d` seconds '
							. '(current value: `%d`). '
    						. 'For better security, enable a long HSTS policy. ',
    						[$minimumSeconds, $transportSecurityValidity]
						) . "\n";
					}
				} elseif (!empty($transportSecurityValidity)) {
					$msg .= $this->l10n->t(
						'- The `Strict-Transport-Security` HTTP header is malformed: `%s`. '
						. 'For better security, configure a valid HSTS policy. ',
						[$transportSecurityValidity]
					) . "\n";
				} else {
					$msg .= $this->l10n->t(
						'- The `Strict-Transport-Security` HTTP header is not set to the recommended minimum of `%d` seconds. '
						. 'For better security, enable HSTS. ',
						[$minimumSeconds]
					) . "\n";
				}

				if (!empty($msg)) {
					return SetupResult::warning(
						$this->l10n->t('Some headers are not set correctly on your instance.') . "\n"
						. $msg . "\n"
						. 'If you believe this is incorrect, review your `overwrite.cli.url` and `trusted_domains` settings. '
    					. 'These settings may include URLs that do not use HTTPS or bypass your reverse proxy, '
						. 'which can affect header checks. '
						. 'Additionally, ensure your DNS records and server configuration are consistent with your HTTPS setup.',
						$this->urlGenerator->linkToDocs('admin-security'),
						$msgParameters,
					);
				}
				// Skip the other requests if one works
				$works = true;
				break;
			}
			// If 'works' is null then we could not connect to the server
			if ($works === null) {
				return SetupResult::info(
					$this->l10n->t('Could not check that your web server serves security headers correctly. Please check manually.'),
					$this->urlGenerator->linkToDocs('admin-security'),
				);
			}
			// Otherwise if we fail we can abort here
			if ($works === false) {
				return SetupResult::warning(
					$this->l10n->t('Could not check that your web server serves security headers correctly, unable to query `%s`', [$url]),
					$this->urlGenerator->linkToDocs('admin-security'),
				);
			}
		}
		return SetupResult::success(
			$this->l10n->t('Your server is correctly configured to send security headers.')
		);
	}
}
