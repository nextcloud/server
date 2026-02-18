<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\SetupCheck;

use Generator;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use Psr\Log\LoggerInterface;

/**
 * Provides common functionality for setup checks that require sending HTTP requests
 * to the same server and analyzing the responses.
 *
 * This trait assists with:
 * - Determining all possible server URLs to test, including trusted domains and CLI overrides.
 * - Running HTTP requests with configurable options for SSL, error handling, and custom client options.
 * - Generating helpful error messages when server connectivity is unavailable.
 * - Normalizing URLs and building request options for consistent server checks.
 *
 * Intended only for use in Nextcloud setup checks.
 *
 * @since 31.0.0
 */
trait CheckServerResponseTrait {
	protected IConfig $config;
	protected IURLGenerator $urlGenerator;
	protected IClientService $clientService;
	protected LoggerInterface $logger;

	/**
	 * Generates a help string explaining what needs to be configured
	 * for local server connectivity checks to succeed.
	 *
	 * Used primarily in the event a check is unable to fetch any results.
	 *
	 * @return string Local server configuration help text
	 * @since 31.0.0
	 */
	protected function serverConfigHelp(): string {
		$l10n = \OCP\Server::get(IFactory::class)->get('lib');
		// TODO: Technically it's necessary the web server, but the PHP SAPI.
		return $l10n->t(
			'This check failed because your web server was unable to connect to itself.' . "\n\n"
			. 'To fix this, please ensure:' . "\n"
			. '- The server can resolve and connect to at least one of its configured `trusted_domains`, or the value set in `overwrite.cli.url`.' . "\n"
			. '- There are no DNS mismatches, or outbound firewall rules blocking connections.' 
		);
	}

	/**
	 * Builds a list of possible absolute URLs for local server request tests.
	 * Considers trusted domains, CLI overwrite URL, and the configured webroot.
	 *
	 * @param string $url The absolute path to test (starts with /, does not include host)
	 * @param bool $isRootRequest If true, removes webroot from URL and host (for root path requests like '/.well-known')
	 * @return list<string> List of possible absolute URLs for testing
	 * @since 31.0.0
	 */
	protected function getTestUrls(string $url, bool $isRootRequest = false): array {
		$url = '/' . ltrim($url, '/');

		$webroot = rtrim($this->urlGenerator->getWebroot(), '/');
		if ($isRootRequest === false && $webroot !== '' && str_starts_with($url, $webroot)) {
			// The URL contains the web-root but also the base url does so,
			// so we need to remove the web-root from the URL.
			$url = substr($url, strlen($webroot));
		}

		// Base URLs to test
		$baseUrls = [];

		// Try overwrite.cli.url first, it’s supposed to be how the server contacts itself
		$cliUrl = $this->config->getSystemValueString('overwrite.cli.url', '');
		if ($cliUrl !== '') {
			// The CLI URL already contains the web-root, so we need to normalize it if requested
			$baseUrls[] = $this->normalizeUrl(
				$cliUrl,
				$isRootRequest
			);
		}

		// Try URL generator second
		// The base URL also contains the webroot so also normalize it
		$baseUrls[] = $this->normalizeUrl(
			$this->urlGenerator->getBaseUrl(),
			$isRootRequest
		);

		/* Last resort: trusted domains */
		$trustedDomains = $this->config->getSystemValue('trusted_domains', []);
		foreach ($trustedDomains as $host) {
			if (str_contains($host, '*')) {
				/* Ignore domains with a wildcard */
				continue;
			}
			$baseUrls[] = $this->normalizeUrl("https://$host$webroot", $isRootRequest);
			$baseUrls[] = $this->normalizeUrl("http://$host$webroot", $isRootRequest);
		}

		return array_map(fn (string $host) => $host . $url, array_values(array_unique($baseUrls)));
	}

	/**
	 * Executes HTTP requests against all possible local server URLs for a given path.
	 *
	 * Yields responses for each successful request; logs and skips on failure (can be overridden).
	 *
	 * @param string $method HTTP method to use (e.g., 'GET', 'POST')
	 * @param string $url Absolute path to check (with webroot, without host); can be the output of `IURLGenerator`
	 * @param array{ignoreSSL?: bool, httpErrors?: bool, options?: array} $options HTTP client options, such as:
	 * - 'ignoreSSL': Ignore invalid SSL certificates (e.g., self-signed).
	 * - 'httpErrors': Whether to ignore requests with HTTP error (4xx/5xx) responses.
	 *   True by default (i.e., moves on to the next URL); set to false to not ignore erroneous responses.
	 * - 'options': Additional options for the HTTP client (see {@see OCP\Http\Client\IClient}).
	 * @param bool $isRootRequest If true, targets the host's root path.
	 * @since 31.0.0
	 */
	protected function runRequest(string $method, string $url, array $options = [], bool $isRootRequest = false): Generator {
		$options = array_merge(['ignoreSSL' => true, 'httpErrors' => true], $options);

		$client = $this->clientService->newClient();
		$requestOptions = $this->getRequestOptions($options['ignoreSSL'], $options['httpErrors']);
		$requestOptions = array_merge($requestOptions, $options['options'] ?? []);

		foreach ($this->getTestUrls($url, $isRootRequest) as $testURL) {
			try {
				yield $client->request($method, $testURL, $requestOptions);
			} catch (\Throwable $e) {
				$this->logger->debug('Can not connect to local server for running setup checks', ['exception' => $e, 'url' => $testURL]);
			}
		}
	}

	/**
	 * Builds HTTP client options for request execution.
	 *
	 * @param bool $ignoreSSL If true, disables SSL verification.
	 * @param bool $httpErrors If true, sets whether HTTP error responses should trigger exceptions.
	 * @since 31.0.0
	 */
	private function getRequestOptions(bool $ignoreSSL, bool $httpErrors): array {
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

	/**
	 * Normalizes a URL by removing trailing slashes and, optionally, the webroot.
	 *
	 * @param string $url Absolute URL containing scheme, host, and optionally the webroot.
	 * @param bool $removeWebroot If true, removes the webroot from the URL, returning only the scheme, host, and optional port.
	 * @throws \InvalidArgumentException If the URL is missing a scheme or host.
	 * @since 31.0.0
	 */
	private function normalizeUrl(string $url, bool $removeWebroot): string {
		if ($removeWebroot) {
			$segments = parse_url($url);
			if (!isset($segments['scheme']) || !isset($segments['host'])) {
				throw new \InvalidArgumentException('URL is missing scheme or host');
			}

			$port = isset($segments['port']) ? (':' . $segments['port']) : '';
			return $segments['scheme'] . '://' . $segments['host'] . $port;
		}
		return rtrim($url, '/');
	}
}
