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
 * Common trait for setup checks that need to use requests to the same server and check the response
 * @since 31.0.0
 */
trait CheckServerResponseTrait {
	protected IConfig $config;
	protected IURLGenerator $urlGenerator;
	protected IClientService $clientService;
	protected LoggerInterface $logger;

	/**
	 * Common helper string in case a check could not fetch any results
	 * @since 31.0.0
	 */
	protected function serverConfigHelp(): string {
		$l10n = \OCP\Server::get(IFactory::class)->get('lib');
		return $l10n->t('To allow this check to run you have to make sure that your Web server can connect to itself. Therefore it must be able to resolve and connect to at least one of its `trusted_domains` or the `overwrite.cli.url`. This failure may be the result of a server-side DNS mismatch or outbound firewall rule.');
	}

	/**
	 * Get all possible URLs that need to be checked for a local request test.
	 * This takes all `trusted_domains` and the CLI overwrite URL into account.
	 *
	 * @param string $url The absolute path (absolute URL without host but with web-root) to test starting with a /
	 * @param bool $isRootRequest Set to remove the web-root from URL and host (e.g. when requesting a path in the domain root like '/.well-known')
	 * @return list<string> List of possible absolute URLs
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
	 * Run a HTTP request to check header
	 * @param string $method The HTTP method to use
	 * @param string $url The absolute path (URL with webroot but without host) to check, can be the output of `IURLGenerator`
	 * @param bool $isRootRequest If set the webroot is removed from URLs to make the request target the host's root. Example usage are the /.well-known URLs in the root path.
	 * @param array{ignoreSSL?: bool, httpErrors?: bool, options?: array} $options HTTP client related options, like
	 *                                                                             [
	 *                                                                             // Ignore invalid SSL certificates (e.g. self signed)
	 *                                                                             'ignoreSSL' => true,
	 *                                                                             // Ignore requests with HTTP errors (will not yield if request has a 4xx or 5xx response)
	 *                                                                             'httpErrors' => true,
	 *                                                                             // Additional options for the HTTP client (see `IClient`)
	 *                                                                             'options' => [],
	 *                                                                             ]
	 *
	 * @return Generator<int, IResponse>
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
	 * Get HTTP client options
	 * @param bool $ignoreSSL If set SSL errors are ignored (e.g. self-signed certificates)
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
	 * Strip a trailing slash and remove the webroot if requested.
	 * @param string $url The URL to normalize. Should be an absolute URL containing scheme, host and optionally web-root.
	 * @param bool $removeWebroot If set the web-root is removed from the URL and an absolute URL with only the scheme and host (optional port) is returned
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
