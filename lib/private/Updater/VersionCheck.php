<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Updater;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\Support\Subscription\IRegistry;
use OCP\Util;

class VersionCheck {
	public function __construct(
		private IClientService $clientService,
		private IConfig $config,
		private IUserManager $userManager,
		private IRegistry $registry,
	) {
	}


	/**
	 * Check if a new version is available
	 *
	 * @return array|bool
	 */
	public function check() {
		// If this server is set to have no internet connection this is all not needed
		if (!$this->config->getSystemValueBool('has_internet_connection', true)) {
			return false;
		}

		// Look up the cache - it is invalidated all 30 minutes
		if (((int)$this->config->getAppValue('core', 'lastupdatedat') + 1800) > time()) {
			return json_decode($this->config->getAppValue('core', 'lastupdateResult'), true);
		}

		$updaterUrl = $this->config->getSystemValueString('updater.server.url', 'https://updates.nextcloud.com/updater_server/');

		$this->config->setAppValue('core', 'lastupdatedat', (string)time());

		if ($this->config->getAppValue('core', 'installedat', '') === '') {
			$this->config->setAppValue('core', 'installedat', (string)microtime(true));
		}

		$version = Util::getVersion();
		$version['installed'] = $this->config->getAppValue('core', 'installedat');
		$version['updated'] = $this->config->getAppValue('core', 'lastupdatedat');
		$version['updatechannel'] = \OC_Util::getChannel();
		$version['edition'] = '';
		$version['build'] = \OC_Util::getBuild();
		$version['php_major'] = PHP_MAJOR_VERSION;
		$version['php_minor'] = PHP_MINOR_VERSION;
		$version['php_release'] = PHP_RELEASE_VERSION;
		$version['category'] = $this->computeCategory();
		$version['isSubscriber'] = (int) $this->registry->delegateHasValidSubscription();
		$versionString = implode('x', $version);

		//fetch xml data from updater
		$url = $updaterUrl . '?version=' . $versionString;

		$tmp = [];
		try {
			$xml = $this->getUrlContent($url);
		} catch (\Exception $e) {
			return false;
		}

		if ($xml) {
			if (\LIBXML_VERSION < 20900) {
				$loadEntities = libxml_disable_entity_loader(true);
				$data = @simplexml_load_string($xml);
				libxml_disable_entity_loader($loadEntities);
			} else {
				$data = @simplexml_load_string($xml);
			}
			if ($data !== false) {
				$tmp['version'] = (string)$data->version;
				$tmp['versionstring'] = (string)$data->versionstring;
				$tmp['url'] = (string)$data->url;
				$tmp['web'] = (string)$data->web;
				$tmp['changes'] = isset($data->changes) ? (string)$data->changes : '';
				$tmp['autoupdater'] = (string)$data->autoupdater;
				$tmp['eol'] = isset($data->eol) ? (string)$data->eol : '0';
			} else {
				libxml_clear_errors();
			}
		}

		// Cache the result
		$this->config->setAppValue('core', 'lastupdateResult', json_encode($tmp));
		return $tmp;
	}

	/**
	 * @codeCoverageIgnore
	 * @param string $url
	 * @return resource|string
	 * @throws \Exception
	 */
	protected function getUrlContent($url) {
		$client = $this->clientService->newClient();
		$response = $client->get($url);
		return $response->getBody();
	}

	private function computeCategory(): int {
		$categoryBoundaries = [
			100,
			500,
			1000,
			5000,
			10000,
			100000,
			1000000,
		];

		$nbUsers = $this->userManager->countSeenUsers();
		foreach ($categoryBoundaries as $categoryId => $boundary) {
			if ($nbUsers <= $boundary) {
				return $categoryId;
			}
		}

		return count($categoryBoundaries);
	}
}
