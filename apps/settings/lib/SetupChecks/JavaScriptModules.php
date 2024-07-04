<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
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

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Checks if the webserver serves '.mjs' files using the correct MIME type
 */
class JavaScriptModules implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IURLGenerator $urlGenerator,
		private IClientService $clientService,
		private LoggerInterface $logger,
	) {
	}

	public function getCategory(): string {
		return 'network';
	}

	public function getName(): string {
		return $this->l10n->t('JavaScript modules support');
	}

	public function run(): SetupResult {
		$testFile = $this->urlGenerator->linkTo('settings', 'js/esm-test.mjs');
		$testURLs = array_merge(
			[$this->urlGenerator->getAbsoluteURL($testFile)],
			array_map(fn (string $host): string => $host . $testFile, $this->config->getSystemValue('trusted_domains', []))
		);

		$gotResponse = false;
		foreach ($testURLs as $testURL) {
			try {
				$client = $this->clientService->newClient();
				$response = $client->head($testURL, [
					'connect_timeout' => 10,
					// Disable SSL certificate checks to allow self signed certs
					'verify' => false,
					// Allow to connect to local server
					'nextcloud' => [
						'allow_local_address' => true,
					],
				]);
				$gotResponse = true;
				if (preg_match('/(text|application)\/javascript/i', $response->getHeader('Content-Type'))) {
					return SetupResult::success();
				}
			} catch (\Throwable $e) {
				$this->logger->debug('Can not connect to local server for checking JavaScript modules support', ['exception' => $e, 'url' => $testURL]);
			}
		}
		if (!$gotResponse) {
			return SetupResult::warning($this->l10n->t('Could not check for JavaScript support. Please check manually if your webserver serves `.mjs` files using the JavaScript MIME type.'));
		}
		return SetupResult::error($this->l10n->t('Your webserver does not serve `.mjs` files using the JavaScript MIME type. This will break some apps by preventing browsers from executing the JavaScript files. You should configure your webserver to serve `.mjs` files with either the `text/javascript` or `application/javascript` MIME type.'));
	}
}
