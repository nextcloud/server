<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
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
			foreach ($this->runRequest('HEAD', $provider, ['httpErrors' => false]) as $response) {
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
On Nginx those are typically the lines starting with "location ~" that need an update.', [join(', ', array_map(fn ($s) => '"' . $s . '"', $missingProviders))]),
			$this->urlGenerator->linkToDocs('admin-nginx'),
		);
	}
}
