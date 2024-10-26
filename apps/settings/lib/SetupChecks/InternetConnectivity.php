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
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;
use Psr\Log\LoggerInterface;

/**
 * Checks if the server can connect to the internet using HTTPS and HTTP
 */
class InternetConnectivity implements ISetupCheck {
	public function __construct(
		private IL10N $l10n,
		private IConfig $config,
		private IClientService $clientService,
		private LoggerInterface $logger,
	) {
	}

	public function getCategory(): string {
		return 'network';
	}

	public function getName(): string {
		return $this->l10n->t('Internet connectivity');
	}

	public function run(): SetupResult {
		if ($this->config->getSystemValue('has_internet_connection', true) === false) {
			return SetupResult::success($this->l10n->t('Internet connectivity is disabled in configuration file.'));
		}

		$siteArray = $this->config->getSystemValue('connectivity_check_domains', [
			'www.nextcloud.com', 'www.startpage.com', 'www.eff.org', 'www.edri.org'
		]);

		foreach ($siteArray as $site) {
			if ($this->isSiteReachable($site)) {
				return SetupResult::success();
			}
		}
		return SetupResult::warning($this->l10n->t('This server has no working internet connection: Multiple endpoints could not be reached. This means that some of the features like mounting external storage, notifications about updates or installation of third-party apps will not work. Accessing files remotely and sending of notification emails might not work, either. Establish a connection from this server to the internet to enjoy all features.'));
	}

	/**
	 * Checks if the Nextcloud server can connect to a specific URL
	 * @param string $site site domain or full URL with http/https protocol
	 */
	private function isSiteReachable(string $site): bool {
		try {
			$client = $this->clientService->newClient();
			// if there is no protocol, test http:// AND https://
			if (preg_match('/^https?:\/\//', $site) !== 1) {
				$httpSite = 'http://' . $site . '/';
				$client->get($httpSite);
				$httpsSite = 'https://' . $site . '/';
				$client->get($httpsSite);
			} else {
				$client->get($site);
			}
		} catch (\Exception $e) {
			$this->logger->error('Cannot connect to: ' . $site, [
				'app' => 'internet_connection_check',
				'exception' => $e,
			]);
			return false;
		}
		return true;
	}
}
