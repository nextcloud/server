<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
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

use Generator;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Common trait for setup checks that need to use requests to the same server and check the response
 */
trait CheckServerResponseTrait {
	protected IConfig $config;
	protected IURLGenerator $urlGenerator;
	protected IClientService $clientService;
	protected IL10N $l10n;
	protected LoggerInterface $logger;

	/**
	 * Common helper string in case a check could not fetch any results
	 */
	protected function serverConfigHelp(): string {
		return $this->l10n->t('To allow this check to run you have to make sure that your webserver can connect to itself. Therefor it must be able to resolve and connect to at least one its `trusted_domains` or the `overwrite.cli.url`.');
	}

	/**
	 * Get all possible URLs that need to be checked for a local request test.
	 * This takes all `trusted_domains` and the CLI overwrite URL into account.
	 *
	 * @param string $url The relative URL to test starting with a /
	 * @return list<string> List of possible absolute URLs
	 */
	protected function getTestUrls(string $url, bool $removeWebroot): array {
		$url = '/' . ltrim($url, '/');

		$webroot = rtrim($this->urlGenerator->getWebroot(), '/');
		// Similar to `getAbsoluteURL` of URLGenerator:
		// The Nextcloud web root could already be prepended.
		if ($webroot !== '' && str_starts_with($url, $webroot)) {
			$url = substr($url, strlen($webroot));
		}

		$hosts = [];

		/* Try overwrite.cli.url first, it’s supposed to be how the server contacts itself */
		$cliUrl = $this->config->getSystemValueString('overwrite.cli.url', '');
		if ($cliUrl !== '') {
			$hosts[] = $this->normalizeUrl(
				$cliUrl,
				$webroot,
				$removeWebroot
			);
		}

		/* Try URL generator second */
		$hosts[] = $this->normalizeUrl(
			$this->urlGenerator->getBaseUrl(),
			$webroot,
			$removeWebroot
		);

		/* Last resort: trusted domains */
		$trustedDomains = $this->config->getSystemValue('trusted_domains', []);
		foreach ($trustedDomains as $host) {
			if (str_contains($host, '*')) {
				/* Ignore domains with a wildcard */
				continue;
			}
			$hosts[] = $this->normalizeUrl("https://$host$webroot", $webroot, $removeWebroot);
			$hosts[] = $this->normalizeUrl("http://$host$webroot", $webroot, $removeWebroot);
		}

		return array_map(fn (string $host) => $host . $url, array_values(array_unique($hosts)));
	}

	/**
	 * Strip a trailing slash and remove the webroot if requested.
	 */
	protected function normalizeUrl(string $url, string $webroot, bool $removeWebroot): string {
		$url = rtrim($url, '/');
		if ($removeWebroot && $webroot !== '' && str_ends_with($url, $webroot)) {
			$url = substr($url, 0, -strlen($webroot));
		}
		return rtrim($url, '/');
	}

	/**
	 * Run a HTTP request to check header
	 * @param string $method The HTTP method to use
	 * @param string $url The relative URL to check (e.g. output of IURLGenerator)
	 * @param bool $removeWebroot Remove the webroot from the URL (handle URL as relative to domain root)
	 * @param array{ignoreSSL?: bool, httpErrors?: bool, options?: array} $options Additional options, like
	 *                                                 [
	 *                                                  // Ignore invalid SSL certificates (e.g. self signed)
	 *                                                  'ignoreSSL' => true,
	 *                                                  // Ignore requests with HTTP errors (will not yield if request has a 4xx or 5xx response)
	 *                                                  'httpErrors' => true,
	 *                                                 ]
	 *
	 * @return Generator<int, IResponse>
	 */
	protected function runRequest(string $method, string $url, array $options = [], bool $removeWebroot = false): Generator {
		$options = array_merge(['ignoreSSL' => true, 'httpErrors' => true], $options);

		$client = $this->clientService->newClient();
		$requestOptions = $this->getRequestOptions($options['ignoreSSL'], $options['httpErrors']);
		$requestOptions = array_merge($requestOptions, $options['options'] ?? []);

		foreach ($this->getTestUrls($url, $removeWebroot) as $testURL) {
			try {
				yield $client->request($method, $testURL, $requestOptions);
			} catch (\Throwable $e) {
				$this->logger->debug('Can not connect to local server for running setup checks', ['exception' => $e, 'url' => $testURL]);
			}
		}
	}

	/**
	 * Run a HEAD request to check header
	 * @param string $url The relative URL to check (e.g. output of IURLGenerator)
	 * @param bool $ignoreSSL Ignore SSL certificates
	 * @param bool $httpErrors Ignore requests with HTTP errors (will not yield if request has a 4xx or 5xx response)
	 * @param bool $removeWebroot Remove the webroot from the URL (handle URL as relative to domain root)
	 * @return Generator<int, IResponse>
	 */
	protected function runHEAD(string $url, bool $ignoreSSL = true, bool $httpErrors = true, bool $removeWebroot = false): Generator {
		return $this->runRequest('HEAD', $url, ['ignoreSSL' => $ignoreSSL, 'httpErrors' => $httpErrors], $removeWebroot);
	}

	protected function getRequestOptions(bool $ignoreSSL, bool $httpErrors): array {
		$requestOptions = [
			'connect_timeout' => 10,
			'http_errors' => $httpErrors,
			'nextcloud' => [
				'allow_local_address' => true,
			],
		];
		if ($ignoreSSL) {
			$requestOptions['verify'] = false;
		}
		return $requestOptions;
	}
}
