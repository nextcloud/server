<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Côme Chilliet <come.chilliet@nextcloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Côme Chilliet <come.chilliet@nextcloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
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
