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

/**
 * Common trait for setup checks that need to use requests to the same server and check the response
 */
trait CheckServerResponseTrait {
	protected IConfig $config;
	protected IURLGenerator $urlGenerator;
	protected IClientService $clientService;
	protected IL10N $l10n;

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
	 * @param string $url The relative URL to test
	 * @return string[] List of possible absolute URLs
	 */
	protected function getTestUrls(string $url): array {
		$hosts = $this->config->getSystemValue('trusted_domains', []);
		$cliUrl = $this->config->getSystemValue('overwrite.cli.url', '');
		if ($cliUrl !== '') {
			$hosts[] = $cliUrl;
		}

		$testUrls = array_merge(
			[$this->urlGenerator->getAbsoluteURL($url)],
			array_map(fn (string $host): string => $host . $url, $hosts),
		);

		return $testUrls;
	}

	/**
	 * Run a HEAD request to check header
	 * @param string $url The relative URL to check
	 * @param bool $ignoreSSL Ignore SSL certificates
	 * @param bool $httpErrors Ignore requests with HTTP errors (will not yield if request has a 4xx or 5xx response)
	 * @return Generator<int, IResponse>
	 */
	protected function runHEAD(string $url, bool $ignoreSSL = true, bool $httpErrors = true): Generator {
		$client = $this->clientService->newClient();
		$requestOptions = $this->getRequestOptions($ignoreSSL, $httpErrors);

		foreach ($this->getTestUrls($url) as $testURL) {
			try {
				yield $client->head($testURL, $requestOptions);
			} catch (\Throwable $e) {
				$this->logger->debug('Can not connect to local server for running setup checks', ['exception' => $e, 'url' => $testURL]);
			}
		}
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
