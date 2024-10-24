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

		foreach ($urls as [$verb,$url,$validStatuses]) {
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
					/* Convert to lowercase and remove spaces after comas */
					$value = preg_replace('/,\s+/', ',', strtolower($response->getHeader($header)));
					if ($value !== $expected) {
						if ($accepted !== null && $value === $accepted) {
							$msg .= $this->l10n->t('- The `%1$s` HTTP header is not set to `%2$s`. Some features might not work correctly, as it is recommended to adjust this setting accordingly.', [$header, $expected]) . "\n";
						} else {
							$msg .= $this->l10n->t('- The `%1$s` HTTP header is not set to `%2$s`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.', [$header, $expected]) . "\n";
						}
					}
				}

				$xssFields = array_map('trim', explode(';', $response->getHeader('X-XSS-Protection')));
				if (!in_array('1', $xssFields) || !in_array('mode=block', $xssFields)) {
					$msg .= $this->l10n->t('- The `%1$s` HTTP header does not contain `%2$s`. This is a potential security or privacy risk, as it is recommended to adjust this setting accordingly.', ['X-XSS-Protection', '1; mode=block']) . "\n";
				}

				$referrerPolicy = $response->getHeader('Referrer-Policy');
				if (!preg_match('/(no-referrer(-when-downgrade)?|strict-origin(-when-cross-origin)?|same-origin)(,|$)/', $referrerPolicy)) {
					$msg .= $this->l10n->t(
						'- The `%1$s` HTTP header is not set to `%2$s`, `%3$s`, `%4$s`, `%5$s` or `%6$s`. This can leak referer information. See the {w3c-recommendation}.',
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
						$msg .= $this->l10n->t('- The `Strict-Transport-Security` HTTP header is not set to at least `%d` seconds (current value: `%d`). For enhanced security, it is recommended to use a long HSTS policy.', [$minimumSeconds, $transportSecurityValidity]) . "\n";
					}
				} elseif (!empty($transportSecurityValidity)) {
					$msg .= $this->l10n->t('- The `Strict-Transport-Security` HTTP header is malformed: `%s`. For enhanced security, it is recommended to enable HSTS.', [$transportSecurityValidity]) . "\n";
				} else {
					$msg .= $this->l10n->t('- The `Strict-Transport-Security` HTTP header is not set (should be at least `%d` seconds). For enhanced security, it is recommended to enable HSTS.', [$minimumSeconds]) . "\n";
				}

				if (!empty($msg)) {
					return SetupResult::warning(
						$this->l10n->t('Some headers are not set correctly on your instance') . "\n" . $msg,
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
