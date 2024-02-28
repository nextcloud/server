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

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Checks if the webserver serves the OCM and OCS providers
 */
class OcxProviders implements ISetupCheck {
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
		return $this->l10n->t('OCS provider resolving');
	}

	public function run(): SetupResult {
		// List of providers that work
		$workingProviders = [];
		// List of providers we tested (in case one or multiple do not yield any response)
		$testedProviders = [];
		// All providers that we need to test
		$providers = [
			'/ocm-provider/',
			'/ocs-provider/',
		];

		foreach ($providers as $provider) {
			foreach ($this->runHEAD($this->urlGenerator->getWebroot() . $provider) as $response) {
				$testedProviders[$provider] = true;
				if ($response->getStatusCode() === 200) {
					$workingProviders[] = $provider;
					break;
				}
			}
		}

		if (count($testedProviders) < count($providers)) {
			return SetupResult::warning(
				$this->l10n->t('Could not check if your web server properly resolves the OCM and OCS provider URLs.', ) . "\n" . $this->serverConfigHelp(),
			);
		}

		$missingProviders = array_diff($providers, $workingProviders);
		if (empty($missingProviders)) {
			return SetupResult::success();
		}

		return SetupResult::warning(
			$this->l10n->t('Your web server is not properly set up to resolve %1$s.
This is most likely related to a web server configuration that was not updated to deliver this folder directly.
Please compare your configuration against the shipped rewrite rules in ".htaccess" for Apache or the provided one in the documentation for Nginx.
On Nginx those are typically the lines starting with "location ~" that need an update.', [join(', ', array_map(fn ($s) => '"'.$s.'"', $missingProviders))]),
			$this->urlGenerator->linkToDocs('admin-nginx'),
		);
	}
}
